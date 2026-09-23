<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class OpenCodeClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.opencode.url', 'http://127.0.0.1:4096'), '/');
        $this->apiKey = config('services.opencode.key', '');
        $this->timeout = (int) config('services.opencode.timeout', 180);
    }

    /**
     * Make a request to OpenCode API
     */
    private function request(string $method, string $endpoint, array $data = []): Response
    {
        $url = "{$this->baseUrl}/api{$endpoint}";

        $client = Http::timeout($this->timeout)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        if ($this->apiKey) {
            $client = $client->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ]);
        }

        // Laravel serializes an empty PHP array as JSON [], while OpenCode's
        // session-create endpoint requires an empty JSON object ({}).
        $payload = in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true) && $data === []
            ? new \stdClass()
            : $data;

        return $client->{strtolower($method)}($url, $payload);
    }

    /**
     * Forward an arbitrary documented V2 API operation without changing its
     * path, query string, JSON body, or HTTP method.
     */
    public function proxy(string $method, string $path, array $query = [], array $body = []): Response
    {
        $url = "{$this->baseUrl}/api/" . ltrim($path, '/');
        $client = Http::timeout($this->timeout)
            ->acceptJson();

        if ($this->apiKey) {
            $client = $client->withToken($this->apiKey);
        }

        $options = ['query' => $query];
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $options['json'] = $body === [] ? new \stdClass() : $body;
        }

        return $client->send(strtoupper($method), $url, $options);
    }

    // ==================== Health & Server ====================

    /**
     * Check API health
     */
    public function health(): Response
    {
        return $this->request('GET', '/health');
    }

    /**
     * Get server information
     */
    public function getServer(): Response
    {
        return $this->request('GET', '/server');
    }

    // ==================== Models ====================

    /**
     * List all available models
     */
    public function listModels(): Response
    {
        return $this->request('GET', '/model');
    }

    /**
     * Get default model
     */
    public function getDefaultModel(): Response
    {
        return $this->request('GET', '/model/default');
    }

    // ==================== Agents ====================

    /**
     * List all available agents
     */
    public function listAgents(): Response
    {
        return $this->request('GET', '/agent');
    }

    /**
     * Get specific agent by ID
     */
    public function getAgent(string $agentId): Response
    {
        return $this->request('GET', "/agent/{$agentId}");
    }

    // ==================== Sessions ====================

    /**
     * List all sessions
     */
    public function listSessions(): Response
    {
        return $this->request('GET', '/session');
    }

    /**
     * Create a new session
     */
    public function createSession(array $data = []): Response
    {
        return $this->request('POST', '/session', $data);
    }

    /**
     * Get specific session
     */
    public function getSession(string $sessionId): Response
    {
        return $this->request('GET', "/session/{$sessionId}");
    }

    /**
     * Delete session
     */
    public function deleteSession(string $sessionId): Response
    {
        return $this->request('DELETE', "/session/{$sessionId}");
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(): Response
    {
        return $this->request('GET', '/session/stats');
    }

    /**
     * List active sessions
     */
    public function listActiveSessions(): Response
    {
        return $this->request('GET', '/session/active');
    }

    /**
     * Rename session
     */
    public function renameSession(string $sessionId, string $name): Response
    {
        return $this->request('POST', "/session/{$sessionId}/rename", ['name' => $name]);
    }

    /**
     * Fork session
     */
    public function forkSession(string $sessionId): Response
    {
        return $this->request('POST', "/session/{$sessionId}/fork");
    }

    /**
     * Switch session agent
     */
    public function switchSessionAgent(string $sessionId, string $agentId): Response
    {
        return $this->request('POST', "/session/{$sessionId}/agent", ['agent' => $agentId]);
    }

    /**
     * Switch session model
     */
    public function switchSessionModel(string $sessionId, $model): Response
    {
        $payload = is_array($model) ? $model : [
            'id' => $model,
            'providerID' => 'opencode'
        ];
        return $this->request('POST', "/session/{$sessionId}/model", ['model' => $payload]);
    }

    // ==================== Chat & Messages ====================

    /**
     * Send message to session (main chat endpoint)
     */
    public function sendMessage(string $sessionId, string $message, array $options = []): Response
    {
        // The OpenCode v2 prompt endpoint expects PromptInput, not a raw string.
        $payload = array_merge(['prompt' => ['text' => $message]], $options);
        return $this->request('POST', "/session/{$sessionId}/prompt", $payload);
    }

    /**
     * Get the messages currently available in a session.
     */
    public function getSessionContext(string $sessionId): Response
    {
        return $this->request('GET', "/session/{$sessionId}/context");
    }

    /**
     * Wait for the completed assistant message created after a submitted prompt.
     *
     * Uses two strategies to find the response:
     * 1. Match the promptId against message IDs in context (original approach)
     * 2. Fall back to finding the last assistant message created after the prompt
     *    was submitted (handles cases where promptId doesn't match context IDs)
     */
    public function waitForAssistantMessage(string $sessionId, ?string $promptId = null, ?int $submittedAtMs = null): ?array
    {
        $deadline = microtime(true) + $this->timeout;
        // `POST /prompt` can itself wait until the model has completed.  The
        // timestamp therefore must be captured *before* that request starts;
        // otherwise a completed response is incorrectly treated as an old
        // message and the UI waits until it times out.
        $startTimeMs = $submittedAtMs ?? (int) (microtime(true) * 1000);

        do {
            $response = $this->getSessionContext($sessionId);

            if (!$response->successful()) {
                usleep(1000000); // 1s backoff on failure
                continue;
            }

            $messages = $response->json('data', []);

            // Strategy 1: Find by promptId in context message IDs
            $promptIndex = $promptId ? array_search($promptId, array_column($messages, 'id'), true) : false;
            if ($promptIndex !== false) {
                foreach (array_slice($messages, $promptIndex + 1) as $message) {
                    if (($message['type'] ?? null) === 'assistant' && isset($message['time']['completed'])) {
                        return $message;
                    }
                }
            }

            // Strategy 2: Find the last completed assistant message created after
            // our prompt submission timestamp (works even if promptId doesn't match)
            foreach (array_reverse($messages) as $message) {
                if (($message['type'] ?? null) !== 'assistant') {
                    continue;
                }
                if (!isset($message['time']['completed'])) {
                    continue;
                }
                $createdAt = $message['time']['created'] ?? 0;
                if ($createdAt >= $startTimeMs) {
                    return $message;
                }
            }

            // Keep the UI responsive when the prompt endpoint is asynchronous
            // without putting excessive load on the local OpenCode server.
            usleep(250000);
        } while (microtime(true) < $deadline);

        return null;
    }

    /**
     * Extract the displayable text portions of an OpenCode assistant message.
     */
    public static function getAssistantText(array $message): string
    {
        // OpenCode has used both `content` and `parts` for message chunks
        // across server versions. Accept both so upgrading OpenCode does not
        // make valid assistant messages appear empty in the web UI.
        $parts = $message['content'] ?? $message['parts'] ?? [];

        return collect($parts)
            ->filter(fn ($part) => is_array($part) && ($part['type'] ?? null) === 'text')
            ->pluck('text')
            ->filter()
            ->implode("\n");
    }

    /**
     * Add synthetic message
     */
    public function addSyntheticMessage(string $sessionId, string $message): Response
    {
        return $this->request('POST', "/session/{$sessionId}/synthetic", [
            'message' => $message
        ]);
    }

    /**
     * Run command in session
     */
    public function runCommand(string $sessionId, string $command): Response
    {
        return $this->request('POST', "/session/{$sessionId}/command", [
            'command' => $command
        ]);
    }

    /**
     * Run shell command in session
     */
    public function runShellCommand(string $sessionId, string $command): Response
    {
        return $this->request('POST', "/session/{$sessionId}/shell", [
            'command' => $command
        ]);
    }

    /**
     * Activate skill in session
     */
    public function activateSkill(string $sessionId, string $skillId, array $params = []): Response
    {
        return $this->request('POST', "/session/{$sessionId}/skill", array_merge([
            'skill' => $skillId
        ], $params));
    }

    // ==================== Generation ====================

    /**
     * One-shot text generation
     */
    public function generate(string $prompt, array $options = []): Response
    {
        $payload = array_merge(['prompt' => $prompt], $options);
        return $this->request('POST', '/generate', $payload);
    }

    // ==================== Providers ====================

    /**
     * List all providers
     */
    public function listProviders(): Response
    {
        return $this->request('GET', '/provider');
    }

    /**
     * Get specific provider
     */
    public function getProvider(string $providerId): Response
    {
        return $this->request('GET', "/provider/{$providerId}");
    }

    // ==================== Integrations ====================

    /**
     * List all integrations
     */
    public function listIntegrations(): Response
    {
        return $this->request('GET', '/integration');
    }

    /**
     * Get specific integration
     */
    public function getIntegration(string $integrationId): Response
    {
        return $this->request('GET', "/integration/{$integrationId}");
    }

    /**
     * Connect integration with API key
     */
    public function connectIntegrationWithKey(string $integrationId, string $key, array $data = []): Response
    {
        $payload = array_merge(['key' => $key], $data);
        return $this->request('POST', "/integration/{$integrationId}/connect/key", $payload);
    }

    /**
     * Begin OAuth connection
     */
    public function beginOAuthConnection(string $integrationId): Response
    {
        return $this->request('POST', "/integration/{$integrationId}/connect/oauth");
    }

    /**
     * Check OAuth connection status
     */
    public function getOAuthStatus(string $integrationId, string $attemptId): Response
    {
        return $this->request('GET', "/integration/{$integrationId}/connect/oauth/{$attemptId}");
    }

    /**
     * Complete OAuth connection
     */
    public function completeOAuthConnection(string $integrationId, string $attemptId, array $data = []): Response
    {
        return $this->request('POST', "/integration/{$integrationId}/connect/oauth/{$attemptId}/complete", $data);
    }

    // ==================== Skills ====================

    /**
     * List all available skills
     */
    public function listSkills(): Response
    {
        return $this->request('GET', '/skill');
    }

    // ==================== Filesystem ====================

    /**
     * Read file
     */
    public function readFile(string $path): Response
    {
        return $this->request('GET', '/fs/read/' . ltrim($path, '/'));
    }

    /**
     * List directory
     */
    public function listDirectory(string $path = ''): Response
    {
        return $this->request('GET', '/fs/list', ['path' => $path]);
    }

    /**
     * Find files
     */
    public function findFiles(string $pattern): Response
    {
        return $this->request('GET', '/fs/find', ['pattern' => $pattern]);
    }

    // ==================== Commands ====================

    /**
     * List all available commands
     */
    public function listCommands(): Response
    {
        return $this->request('GET', '/command');
    }

    // ==================== Shell ====================

    /**
     * List running shell commands
     */
    public function listShellCommands(): Response
    {
        return $this->request('GET', '/shell');
    }

    /**
     * Run shell command
     */
    public function executeShellCommand(string $command, string $workdir = ''): Response
    {
        $data = ['command' => $command];
        if ($workdir) {
            $data['workdir'] = $workdir;
        }
        return $this->request('POST', '/shell', $data);
    }

    /**
     * Get shell command output
     */
    public function getShellOutput(string $id): Response
    {
        return $this->request('GET', "/shell/{$id}/output");
    }

    /**
     * Get shell command status
     */
    public function getShellStatus(string $id): Response
    {
        return $this->request('GET', "/shell/{$id}");
    }

    /**
     * Delete/stop shell command
     */
    public function stopShellCommand(string $id): Response
    {
        return $this->request('DELETE', "/shell/{$id}");
    }

    // ==================== Location ====================

    /**
     * Get current location/workspace info
     */
    public function getLocation(): Response
    {
        return $this->request('GET', '/location');
    }

    // ==================== Plugins ====================

    /**
     * List available plugins
     */
    public function listPlugins(): Response
    {
        return $this->request('GET', '/plugin');
    }

    // ==================== MCP (Model Context Protocol) ====================

    /**
     * List MCP servers
     */
    public function listMcpServers(): Response
    {
        return $this->request('GET', '/mcp');
    }

    /**
     * Add MCP server
     */
    public function addMcpServer(string $server, array $config): Response
    {
        return $this->request('PUT', "/mcp/{$server}", $config);
    }

    /**
     * Remove MCP server
     */
    public function removeMcpServer(string $server): Response
    {
        return $this->request('DELETE', "/mcp/{$server}");
    }

    /**
     * Connect MCP server
     */
    public function connectMcpServer(string $server): Response
    {
        return $this->request('POST', "/mcp/{$server}/connect");
    }

    /**
     * Disconnect MCP server
     */
    public function disconnectMcpServer(string $server): Response
    {
        return $this->request('POST', "/mcp/{$server}/disconnect");
    }

    /**
     * List MCP resources
     */
    public function listMcpResources(): Response
    {
        return $this->request('GET', '/mcp/resource');
    }

    // ==================== Helper Methods ====================

    /**
     * Check if API is online/reachable
     */
    public function isOnline(): bool
    {
        try {
            $response = $this->health();
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get error message from response
     */
    public static function getErrorMessage(Response $response): string
    {
        if ($response->json('error')) {
            return $response->json('error.message', 'Unknown error');
        }
        if ($response->json('message')) {
            return $response->json('message');
        }
        return $response->status() . ': ' . $response->reason();
    }
}
