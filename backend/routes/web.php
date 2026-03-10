<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LegacyController;

Route::get('/', [LegacyController::class, 'pos']);

Route::get('/login', [LegacyController::class, 'login']);
Route::get('/index.php', [LegacyController::class, 'login']);

Route::match(['GET', 'POST'], '/process_login.php', [LegacyController::class, 'processLogin']);

Route::get('/logout', [LegacyController::class, 'logout']);
Route::get('/logout.php', [LegacyController::class, 'logout']);

Route::get('/views/{page}', [LegacyController::class, 'view']);
Route::any('/api/{endpoint}', [LegacyController::class, 'api'])
    ->where('endpoint', '[^/]+');
