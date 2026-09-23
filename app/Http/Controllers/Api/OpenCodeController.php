<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\McpDatabaseBridge;
use App\Services\OpenCodeClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenCodeController extends Controller
{
    private OpenCodeClient $client;
    private McpDatabaseBridge $mcpBridge;

    public function __construct(OpenCodeClient $client, McpDatabaseBridge $mcpBridge)
    {
        $this->client = $client;
        $this->mcpBridge = $mcpBridge;
    }

    /**
     * Health check
     */
    public function health(): JsonResponse
    {
        try {
            $response = $this->client->health();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get server info
     */
    public function getServer(): JsonResponse
    {
        try {
            $response = $this->client->getServer();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if API is online
     */
    public function isOnline(): JsonResponse
    {
        $isOnline = $this->client->isOnline();
        return response()->json(['online' => $isOnline]);
    }

    // ==================== Models ====================

    /**
     * List all models
     */
    public function listModels(): JsonResponse
    {
        try {
            $response = $this->client->listModels();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get default model
     */
    public function getDefaultModel(): JsonResponse
    {
        try {
            $response = $this->client->getDefaultModel();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Agents ====================

    /**
     * List all agents
     */
    public function listAgents(): JsonResponse
    {
        try {
            $response = $this->client->listAgents();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific agent
     */
    public function getAgent(string $agentId): JsonResponse
    {
        try {
            $response = $this->client->getAgent($agentId);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Sessions ====================

    /**
     * List all sessions
     */
    public function listSessions(): JsonResponse
    {
        try {
            $response = $this->client->listSessions();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new session
     */
    public function createSession(Request $request): JsonResponse
    {
        try {
            $response = $this->client->createSession($request->all());

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific session
     */
    public function getSession(string $sessionId): JsonResponse
    {
        try {
            $sessionResponse = $this->client->getSession($sessionId);

            if (!$sessionResponse->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => OpenCodeClient::getErrorMessage($sessionResponse),
                ], $sessionResponse->status());
            }

            $sessionData = $sessionResponse->json();

            // Fetch session context (messages)
            $contextResponse = $this->client->getSessionContext($sessionId);
            $messages = [];

            if ($contextResponse->successful()) {
                $rawMessages = $contextResponse->json('data', []);
                foreach ($rawMessages as $msg) {
                    $role = $msg['type'] ?? 'user';
                    $timestamp = isset($msg['time']['created'])
                        ? \Carbon\Carbon::createFromTimestampMs($msg['time']['created'])->toIso8601String()
                        : now()->toIso8601String();

                    if ($role === 'user') {
                        $content = $msg['text'] ?? '';
                        if (str_contains($content, 'User Question:')) {
                            $parts = explode('User Question:', $content, 2);
                            $content = trim(end($parts));
                        }
                    } else {
                        // Extract assistant text using OpenCodeClient helper
                        $content = OpenCodeClient::getAssistantText($msg);
                    }

                    $messages[] = [
                        'role' => $role,
                        'content' => $content,
                        'timestamp' => $timestamp,
                    ];
                }
            }

            $sessionData['messages'] = $messages;

            return response()->json([
                'status' => 'ok',
                'data' => $sessionData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete session
     */
    public function deleteSession(string $sessionId): JsonResponse
    {
        try {
            // Temukan path executable opencode secara dinamis
            $opencode = 'opencode';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $where = shell_exec('where.exe opencode');
                if ($where) {
                    $paths = array_filter(array_map('trim', explode("\n", $where)));
                    foreach ($paths as $path) {
                        if (str_ends_with(strtolower($path), '.cmd') || str_ends_with(strtolower($path), '.bat')) {
                            $opencode = $path;
                            break;
                        }
                    }
                    if ($opencode === 'opencode' && !empty($paths)) {
                        $opencode = array_values($paths)[0];
                    }
                }
            }

            // Coba hapus menggunakan CLI opencode jika tersedia
            $escapedSessionId = escapeshellarg($sessionId);
            $output = shell_exec('"' . $opencode . '" session delete ' . $escapedSessionId . ' 2>&1');

            if ($output && str_contains(strtolower($output), 'deleted')) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Session deleted successfully',
                ]);
            }

            // Fallback ke HTTP API jika CLI gagal
            $response = $this->client->deleteSession($sessionId);

            // Cek apakah HTTP response sukses dan content type-nya adalah JSON (bukan HTML fallback)
            $contentType = $response->header('Content-Type');
            if ($response->successful() && str_contains($contentType, 'application/json')) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Session deleted successfully',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete session: ' . ($output ? trim($output) : OpenCodeClient::getErrorMessage($response)),
            ], $response->status() === 200 ? 500 : $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): JsonResponse
    {
        try {
            $response = $this->client->getSessionStats();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rename session
     */
    public function renameSession(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validated = $request->validate(['name' => 'required|string']);

            // Temukan path executable opencode secara dinamis
            $opencode = 'opencode';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $where = shell_exec('where.exe opencode');
                if ($where) {
                    $paths = array_filter(array_map('trim', explode("\n", $where)));
                    foreach ($paths as $path) {
                        if (str_ends_with(strtolower($path), '.cmd') || str_ends_with(strtolower($path), '.bat')) {
                            $opencode = $path;
                            break;
                        }
                    }
                    if ($opencode === 'opencode' && !empty($paths)) {
                        $opencode = array_values($paths)[0];
                    }
                }
            }

            // Jalankan update database via CLI
            $escapedName = str_replace("'", "''", $validated['name']);
            $sql = "UPDATE session SET title = '{$escapedName}' WHERE id = '{$sessionId}'";
            $escapedSql = escapeshellarg($sql);

            $output = shell_exec('"' . $opencode . '" db ' . $escapedSql . ' 2>&1');

            // Fallback ke HTTP API jika CLI gagal
            $response = $this->client->renameSession($sessionId, $validated['name']);

            // Cek apakah HTTP response sukses dan content type-nya adalah JSON (bukan HTML fallback)
            $contentType = $response->header('Content-Type');
            $httpSuccess = $response->successful() && str_contains($contentType, 'application/json');

            if ($httpSuccess || trim($output) === '') {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Session renamed successfully',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to rename session: ' . ($output ? trim($output) : OpenCodeClient::getErrorMessage($response)),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Switch session agent
     */
    public function switchAgent(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validated = $request->validate(['agent' => 'required|string']);
            $response = $this->client->switchSessionAgent($sessionId, $validated['agent']);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Switch session model
     */
    public function switchModel(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validated = $request->validate(['model' => 'required']);
            $response = $this->client->switchSessionModel($sessionId, $request->input('model'));

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Chat & Messages ====================

    /**
     * Send message to session
     */
    public function sendMessage(Request $request, string $sessionId): JsonResponse
    {
        // Disable PHP max_execution_time limit for long LLM generations
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $validated = $request->validate(['message' => 'required|string']);
            // Exclude UI-only fields that should NOT be forwarded to OpenCode's prompt API
            $options = $request->except(['message', 'include_db_context', 'agent', 'provider']);

            $messageText = $validated['message'];

            // Switch agent if specified by the UI
            if ($request->filled('agent')) {
                try {
                    $this->client->switchSessionAgent($sessionId, $request->input('agent'));
                } catch (\Exception $e) {
                    \Log::warning('Failed to switch agent: ' . $e->getMessage());
                }
            }

            // "Hubungkan Konteks Database (MCP)" toggle: when checked, connect the
            // MySQL MCP server and inject the live students context so OpenCode can
            // answer database questions from real records. When unchecked, disconnect
            // the MCP server and skip injection so the model genuinely cannot reach
            // the database. Defaults to off when the flag is absent.
            $useDbContext = $request->boolean('include_db_context');

            try {
                $this->client->{$useDbContext ? 'connectMcpServer' : 'disconnectMcpServer'}('mysql');
            } catch (\Exception $e) {
                \Log::warning('MCP server gate failed: ' . $e->getMessage());
            }

            if ($useDbContext) {
                try {
                    $mcpContext = $this->mcpBridge->getContext($messageText);
                    $messageText = $mcpContext . "\n\nUser Question: " . $messageText;
                } catch (\Exception $e) {
                    // Silently continue without MCP context if bridge fails
                    \Log::warning('MCP context injection failed: ' . $e->getMessage());
                }
            }

            // Record this before sending. Some OpenCode releases only return
            // after generation is complete, so recording it afterwards makes
            // the completed assistant message look older than the request.
            $submittedAtMs = (int) (microtime(true) * 1000);

            // Send prompt message to OpenCode client
            $response = $this->client->sendMessage($sessionId, $messageText, $options);

            if ($response->successful()) {
                $promptId = $response->json('data.id');
                // Older OpenCode releases return a user-message ID here;
                // newer ones may omit it. The client also matches by the
                // pre-submit timestamp, so both response formats work.
                $assistantMessage = $this->client->waitForAssistantMessage($sessionId, $promptId, $submittedAtMs);

                if (!$assistantMessage) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'OpenCode belum mengirim respons sebelum batas waktu habis.',
                    ], 504);
                }

                $content = OpenCodeClient::getAssistantText($assistantMessage);

                if ($content === '') {
                    $error = $assistantMessage['error']['message'] ?? 'OpenCode tidak menghasilkan respons teks.';

                    return response()->json([
                        'status' => 'error',
                        'message' => $error,
                    ], 502);
                }

                return response()->json([
                    'status' => 'ok',
                    'data' => [
                        'content' => $content,
                        'message' => $assistantMessage,
                    ],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run command
     */
    public function runCommand(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validated = $request->validate(['command' => 'required|string']);
            $response = $this->client->runCommand($sessionId, $validated['command']);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run shell command
     */
    public function runShellCommand(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validated = $request->validate(['command' => 'required|string']);
            $response = $this->client->runShellCommand($sessionId, $validated['command']);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate skill
     */
    public function activateSkill(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validated = $request->validate(['skill' => 'required|string']);
            $params = $request->except('skill');

            $response = $this->client->activateSkill($sessionId, $validated['skill'], $params);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Generation ====================

    /**
     * One-shot text generation
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['prompt' => 'required|string']);
            $options = $request->except('prompt');

            $response = $this->client->generate($validated['prompt'], $options);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Providers ====================

    /**
     * List all providers
     */
    public function listProviders(): JsonResponse
    {
        try {
            $response = $this->client->listProviders();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific provider
     */
    public function getProvider(string $providerId): JsonResponse
    {
        try {
            $response = $this->client->getProvider($providerId);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Skills ====================

    /**
     * List all skills
     */
    public function listSkills(): JsonResponse
    {
        try {
            $response = $this->client->listSkills();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Commands ====================

    /**
     * List all commands
     */
    public function listCommands(): JsonResponse
    {
        try {
            $response = $this->client->listCommands();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Shell ====================

    /**
     * List shell commands
     */
    public function listShellCommands(): JsonResponse
    {
        try {
            $response = $this->client->listShellCommands();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute shell command
     */
    public function executeShellCommand(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['command' => 'required|string']);
            $workdir = $request->input('workdir', '');

            $response = $this->client->executeShellCommand($validated['command'], $workdir);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get shell command output
     */
    public function getShellOutput(string $id): JsonResponse
    {
        try {
            $response = $this->client->getShellOutput($id);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop shell command
     */
    public function stopShellCommand(string $id): JsonResponse
    {
        try {
            $response = $this->client->stopShellCommand($id);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Shell command stopped',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Filesystem ====================

    /**
     * Read file
     */
    public function readFile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['path' => 'required|string']);
            $response = $this->client->readFile($validated['path']);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List directory
     */
    public function listDirectory(Request $request): JsonResponse
    {
        try {
            $path = $request->input('path', '');
            $response = $this->client->listDirectory($path);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find files
     */
    public function findFiles(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['pattern' => 'required|string']);
            $response = $this->client->findFiles($validated['pattern']);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Integrations ====================

    /**
     * List integrations
     */
    public function listIntegrations(): JsonResponse
    {
        try {
            $response = $this->client->listIntegrations();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get integration
     */
    public function getIntegration(string $integrationId): JsonResponse
    {
        try {
            $response = $this->client->getIntegration($integrationId);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Connect integration with key
     */
    public function connectIntegrationWithKey(Request $request, string $integrationId): JsonResponse
    {
        try {
            $validated = $request->validate(['key' => 'required|string']);
            $data = $request->except('key');

            $response = $this->client->connectIntegrationWithKey($integrationId, $validated['key'], $data);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Plugins ====================

    /**
     * List plugins
     */
    public function listPlugins(): JsonResponse
    {
        try {
            $response = $this->client->listPlugins();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== MCP ====================

    /**
     * List MCP servers
     */
    public function listMcpServers(): JsonResponse
    {
        try {
            $response = $this->client->listMcpServers();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List MCP resources
     */
    public function listMcpResources(): JsonResponse
    {
        try {
            $response = $this->client->listMcpResources();

            if ($response->successful()) {
                return response()->json([
                    'status' => 'ok',
                    'data' => $response->json(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => OpenCodeClient::getErrorMessage($response),
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transparent pass-through for every documented OpenCode V2 operation.
     */
    public function proxy(Request $request, ?string $path = ''): \Illuminate\Http\Response
    {
        try {
            $response = $this->client->proxy(
                $request->method(),
                $path ?? '',
                $request->query(),
                $request->json()->all()
            );

            $headers = [];
            foreach (['Content-Type', 'Content-Disposition', 'Cache-Control'] as $header) {
                if ($value = $response->header($header)) {
                    $headers[$header] = $value;
                }
            }

            return response($response->body(), $response->status(), $headers);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }
    }
}
