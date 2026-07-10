<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::query()
            ->select(['id', 'username', 'password_hash', 'role'])
            ->where('username', $data['username'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials.'], 401);
        }

        User::where('id', $user->id)->update(['last_login' => now()]);

        $tokens = $this->issueTokens($user);

        return response()->json([
            'success' => true,
            'token_type' => 'Bearer',
            'access_token' => $tokens['access_token'],
            'access_expires_at' => $tokens['access_expires_at'],
            'refresh_token' => $tokens['refresh_token'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ]);
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $refreshHash = hash('sha256', $data['refresh_token']);

        $token = ApiToken::query()
            ->where('token_hash', $refreshHash)
            ->where('type', 'refresh')
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$token || !$token->user) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired refresh token.'], 401);
        }

        $token->forceFill(['revoked_at' => now()])->save();

        $tokens = $this->issueTokens($token->user);

        return response()->json([
            'success' => true,
            'token_type' => 'Bearer',
            'access_token' => $tokens['access_token'],
            'access_expires_at' => $tokens['access_expires_at'],
            'refresh_token' => $tokens['refresh_token'],
            'refresh_expires_at' => $tokens['refresh_expires_at'],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        ApiToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function issueTokens(User $user): array
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(48));

        $accessExpiresAt = now()->addMinutes(config('auth_tokens.access_ttl_minutes'));
        $refreshExpiresAt = now()->addDays(config('auth_tokens.refresh_ttl_days'));

        ApiToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $accessToken),
            'type' => 'access',
            'expires_at' => $accessExpiresAt,
        ]);

        ApiToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshToken),
            'type' => 'refresh',
            'expires_at' => $refreshExpiresAt,
        ]);

        return [
            'access_token' => $accessToken,
            'access_expires_at' => $accessExpiresAt->toIso8601String(),
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $refreshExpiresAt->toIso8601String(),
        ];
    }
}
