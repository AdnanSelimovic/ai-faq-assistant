<?php

use App\Http\Controllers\EmailLoginController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\KbDocumentController;
use App\Http\Controllers\AskPreferenceController;
use App\Services\AskModeResolver;
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

Route::get('/dashboard', function (\Illuminate\Http\Request $request, AskModeResolver $resolver) {
    return view('dashboard', [
        'askMode' => $resolver->resolve($request),
    ]);
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
        Route::get('/documents/{id}/edit', [KbDocumentController::class, 'edit'])
            ->name('kb.documents.edit');
        Route::patch('/documents/{id}', [KbDocumentController::class, 'update'])
            ->name('kb.documents.update');
        Route::get('/documents/{id}', [KbDocumentController::class, 'show'])
            ->name('kb.documents.show');
        Route::delete('/documents/{id}', [KbDocumentController::class, 'destroy'])
            ->name('kb.documents.destroy');
        Route::post('/documents/{id}/index', [KbDocumentController::class, 'indexDocument'])
            ->name('kb.documents.index-document');
    });

Route::post('/ask', [ChatController::class, 'ask'])
    ->middleware(['auth', 'throttle:ask'])
    ->name('chat.ask');

Route::post('/preferences/ask-mode', [AskPreferenceController::class, 'store'])
    ->middleware('auth')
    ->name('preferences.ask-mode');
