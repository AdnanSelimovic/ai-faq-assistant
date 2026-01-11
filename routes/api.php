<?php

use App\Http\Controllers\Api\KbDocumentApiController;
use App\Http\Controllers\Api\KbSearchApiController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ForceJsonResponse;

Route::middleware([ForceJsonResponse::class, 'auth:sanctum'])->group(function () {
    Route::post('/kb/documents', [KbDocumentApiController::class, 'store']);
    Route::post('/kb/documents/{id}/index', [KbDocumentApiController::class, 'index']);
    Route::get('/kb/documents', [KbDocumentApiController::class, 'list']);

    Route::post('/kb/search', [KbSearchApiController::class, 'search']);

    Route::post('/ask', [ChatController::class, 'ask'])->middleware('throttle:ask');
});
