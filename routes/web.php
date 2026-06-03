<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('handle-login');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('checkLogin')->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    Route::get('/category', [CategoryController::class, 'index'])->name('admin.category');
    Route::post('/category', [CategoryController::class, 'store'])->name('admin.category.store');
    Route::post('/category/{id}/update', [CategoryController::class, 'update'])->name('admin.category.update');
    Route::post('/category/{id}',[CategoryController::class,'delete'])->name('admin.category.delete');
    Route::get('/brand',[BrandController::class,'index'])->name('admin.brand');
});
Route::get('/forgot-password', [AuthController::class, 'formForgotPassword'])->name('forgot-password');
Route::post('/forgot-password', [AuthController::class, 'handleForgotPassword'])->name('handle-forgot-password');
Route::get('/reset-password/{token}', [AuthController::class, 'formResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'handleResetPassword'])->name('password.update');
