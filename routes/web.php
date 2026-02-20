<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\MasterRequestController;
use App\Http\Controllers\RequestAuditController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// страница с заявкой
Route::get('/request', function () {
    return Inertia::render('RequestForm');
})->name('mainPage');

// контроллер для post запроса заявки
Route::post('/requests', [RequestController::class, 'store'])
    ->name('requests.store');

// Сначала специфичные маршруты
Route::middleware(['auth', 'role:1'])->group(function () {
    Route::put('/requestsForMaster/{id}/take', [MasterRequestController::class, 'take'])
        ->name('requests.take');
    Route::put('/requestsForMaster/{id}/complete', [MasterRequestController::class, 'complete'])
        ->name('requests.complete');
    Route::get('/requestsForMaster', [MasterRequestController::class, 'index'])
        ->name('requestsForMaster.index');
});

// Потом общие маршруты
Route::middleware(['auth', 'role:0'])->group(function () {
    Route::put('/requests/{request}/cancel', [RequestController::class, 'cancel'])
        ->name('requests.cancel');
    Route::put('/requests/{request}/assign', [RequestController::class, 'assign'])
        ->name('requests.assign');
    Route::get('/requests', [RequestController::class, 'index'])
        ->name('requests.index');
});
Route::middleware('auth')->group(function () {
    Route::put('/api/requests/{id}/take', [MasterRequestController::class, 'takeApi']);
});

Route::middleware(['auth'])->group(function () {

    
    // История заявки
    Route::get('/requests/{request}/history', [RequestAuditController::class, 'index'])
        ->name('requests.history');
});