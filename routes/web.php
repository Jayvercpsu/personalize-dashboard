<?php

use App\Http\Controllers\AssociateController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HourlyNoteController;
use App\Http\Controllers\ProcessPathController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::post('/notes', [HourlyNoteController::class, 'upsert'])->name('notes.upsert');
    Route::post('/chat', [ChatMessageController::class, 'store'])->name('chat.store');
    Route::post('/chat/reset', [ChatMessageController::class, 'reset'])->name('chat.reset');

    Route::post('/associates', [AssociateController::class, 'store'])->name('associates.store');
    Route::delete('/associates/{associate}', [AssociateController::class, 'destroy'])->name('associates.destroy');

    Route::post('/schedule/generate', [ScheduleController::class, 'generateMonth'])->name('schedule.generate');
    Route::post('/schedule/update', [ScheduleController::class, 'updateDay'])->name('schedule.update');
    Route::post('/schedule/pools', [ScheduleController::class, 'updatePools'])->name('schedule.pools');
    Route::post('/schedule/theme', [ScheduleController::class, 'setTheme'])->name('schedule.theme');

    Route::post('/process-path', [ProcessPathController::class, 'update'])->name('process-path.update');
    Route::get('/process-path/print', [ProcessPathController::class, 'print'])->name('process-path.print');
});
