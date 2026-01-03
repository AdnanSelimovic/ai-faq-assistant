<?php

use App\Http\Controllers\EmailLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [EmailLoginController::class, 'create'])
    ->middleware('guest')
    ->name('login');
Route::post('/login', [EmailLoginController::class, 'store'])
    ->middleware('guest');
Route::post('/logout', [EmailLoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware('auth')
    ->name('dashboard');
