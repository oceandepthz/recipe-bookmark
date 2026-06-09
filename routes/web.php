<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CookingLogController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

// 認証（ゲスト向け）
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// アプリ本体（要ログイン）
Route::middleware('auth')->group(function () {
    Route::get('/', [RecipeController::class, 'index'])->name('recipes.index');

    Route::resource('recipes', RecipeController::class)->except(['index']);

    Route::post('recipes/{recipe}/logs', [CookingLogController::class, 'store'])
        ->name('recipes.logs.store');
    Route::delete('cooking-logs/{cookingLog}', [CookingLogController::class, 'destroy'])
        ->name('cooking-logs.destroy');
});
