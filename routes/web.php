<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route untuk publik
Route::get('/', function () {
    return redirect()->route('login');
});
    
Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login.post');

// Route yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Log routes
    Route::get('/store-logs', [LogController::class, 'storeLogs']);
    Route::get('/logs/{filename}', [LogController::class, 'showLogs']);
    
    // API routes
    Route::get('/api/logs', [DashboardController::class, 'getLogs']);
    Route::get('/api/metrics', [DashboardController::class, 'getMetrics']);
    Route::get('/api/log-stats', [DashboardController::class, 'getLogStats']);
});
