<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use LogicException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_updates_create_an_audit_log_with_old_and_new_values(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $product = Product::query()->create([
            'barcode' => '1234567890',
            'name' => 'Deonat',
            'price' => '150.00',
            'cost_price' => '100.00',
            'stock_quantity' => 10,
        ]);

        $product->price = '165.00';
        $product->save();

        $log = AuditLog::query()
            ->where('auditable_type', 'Product')
            ->where('auditable_id', $product->id)
            ->where('event_type', 'Update')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(['price' => '150.00'], $log->old_values);
        $this->assertSame(['price' => '165.00'], $log->new_values);
        $this->assertSame("User ID {$user->id} Updated Product 'Deonat' price from 150.00 to 165.00", $log->message);
    }

    public function test_sale_status_change_to_refunded_creates_a_refund_audit_log(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $sale = Sale::query()->create([
            'sale_date' => now(),
            'total_amount' => '500.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'payment_method' => 'Cash',
            'cash_received' => '500.00',
            'change_due' => '0.00',
            'cashier_id' => $user->id,
            'status' => 'completed',
        ]);

        $sale->status = 'refunded';
        $sale->save();

        $log = AuditLog::query()
            ->where('auditable_type', 'Transaction')
            ->where('auditable_id', $sale->id)
            ->where('event_type', 'Refund')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(['status' => 'completed'], $log->old_values);
        $this->assertSame(['status' => 'refunded'], $log->new_values);
        $this->assertSame("User ID {$user->id} Refunded Transaction #{$sale->id} status from completed to refunded", $log->message);
    }

    public function test_audit_logs_are_append_only(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        Product::query()->create([
            'barcode' => '99887766',
            'name' => 'Append Test',
            'price' => '75.00',
            'cost_price' => '50.00',
            'stock_quantity' => 4,
        ]);

        $log = AuditLog::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $log->update(['message' => 'Tampered']);
    }

    public function test_category_crud_creates_audit_logs(): void
    {
        $user = $this->createUser();
        Auth::login($user);

        $category = Category::query()->create([
            'name' => 'Personal Care',
            'description' => 'Initial description',
        ]);

        $category->description = 'Updated description';
        $category->save();
        $category->delete();

        $logs = AuditLog::query()
            ->where('auditable_type', 'Category')
            ->where('auditable_id', $category->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $logs);
        $this->assertSame('Create', $logs[0]->event_type);
        $this->assertSame('Update', $logs[1]->event_type);
        $this->assertSame(['description' => 'Initial description'], $logs[1]->old_values);
        $this->assertSame(['description' => 'Updated description'], $logs[1]->new_values);
        $this->assertSame('Delete', $logs[2]->event_type);
    }

    public function test_user_creation_by_admin_creates_an_audit_log(): void
    {
        $admin = $this->createUser();
        Auth::login($admin);

        $createdUser = User::query()->create([
            'username' => 'cashier_101',
            'email' => 'cashier101@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'cashier',
        ]);

        $log = AuditLog::query()
            ->where('auditable_type', 'User')
            ->where('auditable_id', $createdUser->id)
            ->where('event_type', 'Create')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame("User ID {$admin->id} Created User 'cashier_101'", $log->message);
    }

    private function createUser(): User
    {
        return User::query()->create([
            'username' => 'auditor_' . fake()->unique()->numerify('###'),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }
}
