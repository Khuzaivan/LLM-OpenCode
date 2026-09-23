<?php

use App\Http\Controllers\Api\OpenCodeController;
use App\Http\Controllers\Api\McpController;
use App\Http\Controllers\Api\McpTestController;
use Illuminate\Support\Facades\Route;

// ==================== Health & Status ====================
Route::get('/health', [OpenCodeController::class, 'health']);
Route::get('/server', [OpenCodeController::class, 'getServer']);
Route::get('/status', [OpenCodeController::class, 'isOnline']);

// ==================== Models ====================
Route::get('/models', [OpenCodeController::class, 'listModels']);
Route::get('/models/default', [OpenCodeController::class, 'getDefaultModel']);

// ==================== Agents ====================
Route::get('/agents', [OpenCodeController::class, 'listAgents']);
Route::get('/agents/{agentId}', [OpenCodeController::class, 'getAgent']);

// ==================== Sessions ====================
Route::prefix('sessions')->group(function () {
    Route::get('/', [OpenCodeController::class, 'listSessions']);
    Route::post('/', [OpenCodeController::class, 'createSession']);
    Route::get('/stats', [OpenCodeController::class, 'getSessionStats']);
    
    Route::get('/{sessionId}', [OpenCodeController::class, 'getSession']);
    Route::delete('/{sessionId}', [OpenCodeController::class, 'deleteSession']);
    Route::post('/{sessionId}/rename', [OpenCodeController::class, 'renameSession']);
    Route::post('/{sessionId}/switch-agent', [OpenCodeController::class, 'switchAgent']);
    Route::post('/{sessionId}/switch-model', [OpenCodeController::class, 'switchModel']);
    
    // Chat & Messages
    Route::post('/{sessionId}/message', [OpenCodeController::class, 'sendMessage']);
    Route::post('/{sessionId}/command', [OpenCodeController::class, 'runCommand']);
    Route::post('/{sessionId}/shell', [OpenCodeController::class, 'runShellCommand']);
    Route::post('/{sessionId}/skill', [OpenCodeController::class, 'activateSkill']);
});

// ==================== Generation ====================
Route::post('/generate', [OpenCodeController::class, 'generate']);

// ==================== Providers ====================
Route::get('/providers', [OpenCodeController::class, 'listProviders']);
Route::get('/providers/{providerId}', [OpenCodeController::class, 'getProvider']);

// ==================== Skills ====================
Route::get('/skills', [OpenCodeController::class, 'listSkills']);

// ==================== Commands ====================
Route::get('/commands', [OpenCodeController::class, 'listCommands']);

// ==================== Shell ====================
Route::prefix('shell')->group(function () {
    Route::get('/', [OpenCodeController::class, 'listShellCommands']);
    Route::post('/', [OpenCodeController::class, 'executeShellCommand']);
    Route::get('/{id}/output', [OpenCodeController::class, 'getShellOutput']);
    Route::delete('/{id}', [OpenCodeController::class, 'stopShellCommand']);
});

// ==================== Filesystem ====================
Route::prefix('fs')->group(function () {
    Route::get('/read', [OpenCodeController::class, 'readFile']);
    Route::get('/list', [OpenCodeController::class, 'listDirectory']);
    Route::get('/find', [OpenCodeController::class, 'findFiles']);
});

// ==================== Integrations ====================
Route::prefix('integrations')->group(function () {
    Route::get('/', [OpenCodeController::class, 'listIntegrations']);
    Route::get('/{integrationId}', [OpenCodeController::class, 'getIntegration']);
    Route::post('/{integrationId}/connect-key', [OpenCodeController::class, 'connectIntegrationWithKey']);
});

// ==================== Plugins ====================
Route::get('/plugins', [OpenCodeController::class, 'listPlugins']);

// ==================== MCP (OpenCode) ====================
Route::prefix('mcp')->group(function () {
    Route::get('/', [OpenCodeController::class, 'listMcpServers']);
    Route::get('/resources', [OpenCodeController::class, 'listMcpResources']);
});

// ==================== MCP Database Bridge ====================
Route::prefix('mcp/database')->group(function () {
    Route::get('/resources', [McpController::class, 'listResources']);
    Route::get('/resource/{uri}', [McpController::class, 'readResource'])->where('uri', '.*');
    Route::get('/search', [McpController::class, 'searchStudents']);
    Route::get('/schema/{table}', [McpController::class, 'getSchema']);
    Route::post('/query', [McpController::class, 'executeQuery']);
    Route::get('/context', [McpController::class, 'getContext']);
});

// ==================== Test / Verification ====================
Route::prefix('test')->group(function () {
    Route::get('/mcp-connection', [McpTestController::class, 'testConnection']);
    Route::get('/mcp-search', [McpTestController::class, 'testSearch']);
    Route::get('/mcp-schema', [McpTestController::class, 'testSchema']);
    Route::post('/mcp-query', [McpTestController::class, 'testQuery']);
});

// Raw V2 proxy. For example: GET /api/v2/config -> GET /api/config on OpenCode.
// This preserves every documented OpenCode V2 operation, including new API paths.
Route::any('/v2/{path?}', [OpenCodeController::class, 'proxy'])
    ->where('path', '.*');
