<?php

use App\Http\Controllers\EmailLoginController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\KbDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [EmailLoginController::class, 'create'])
    ->middleware('guest')
    ->name('login');
Route::post('/login', [EmailLoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login']);
Route::post('/logout', [EmailLoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')
    ->prefix('kb')
    ->group(function () {
        Route::get('/documents', [KbDocumentController::class, 'index'])
            ->name('kb.documents.index');
        Route::get('/documents/create', [KbDocumentController::class, 'create'])
            ->name('kb.documents.create');
        Route::post('/documents', [KbDocumentController::class, 'store'])
            ->name('kb.documents.store');
        Route::get('/documents/{id}', [KbDocumentController::class, 'show'])
            ->name('kb.documents.show');
        Route::post('/documents/{id}/index', [KbDocumentController::class, 'indexDocument'])
            ->name('kb.documents.index-document');
    });

Route::post('/ask', [ChatController::class, 'ask'])
    ->middleware(['auth', 'throttle:ask'])
    ->name('chat.ask');
