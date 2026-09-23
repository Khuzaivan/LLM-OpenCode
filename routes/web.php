<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ApiDashboardController;
use App\Http\Controllers\OpenCodeController;
use App\Http\Controllers\OpenRouterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChatController::class, 'index']);

Route::get('/opencode', [OpenCodeController::class, 'index'])
	->name('opencode.index');

Route::post('/session', [OpenCodeController::class, 'createSession'])
	->name('opencode.session.create');

Route::post('/session/{sessionId}/message', [OpenCodeController::class, 'sendMessage'])
	->name('opencode.message');

Route::get('/session/{sessionId}/messages', [OpenCodeController::class, 'messages'])
	->name('opencode.messages');

Route::delete('/session/{sessionId}', [OpenCodeController::class, 'deleteSession'])
	->name('opencode.session.delete');

Route::get('/api-dashboard', [ApiDashboardController::class, 'index'])
	->name('api.dashboard');

Route::post('/api-dashboard/call', [ApiDashboardController::class, 'call'])
	->name('api.dashboard.call');

Route::post('/openrouter/message', [OpenRouterController::class, 'sendMessage'])
	->name('openrouter.message');
