<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenCodeService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.opencode.url'),
            '/'
        );
    }

    protected function client()
    {
        return Http::timeout(120)
            ->acceptJson()
            ->asJson();
    }

    /*
    |--------------------------------------------------------------------------
    | GENERIC REQUEST
    |--------------------------------------------------------------------------
    */

    public function request(
        string $method,
        string $endpoint,
        array $data = []
    ) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        return match (strtoupper($method)) {

            'GET' => $this->client()
                ->get($url, $data),

            'POST' => $this->client()
                ->post($url, $data),

            'PATCH' => $this->client()
                ->patch($url, $data),

            'PUT' => $this->client()
                ->put($url, $data),

            'DELETE' => $this->client()
                ->delete($url, $data),

            default => throw new \Exception(
                "HTTP method tidak didukung."
            )
        };
    }


    /*
    |--------------------------------------------------------------------------
    | GLOBAL
    |--------------------------------------------------------------------------
    */

    public function health()
    {
        return $this->request(
            'GET',
            '/global/health'
        )->throw()->json();
    }

    public function event()
    {
        return $this->request(
            'GET',
            '/event'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROJECT
    |--------------------------------------------------------------------------
    */

    public function projects()
    {
        return $this->request(
            'GET',
            '/project'
        )->throw()->json();
    }

    public function currentProject()
    {
        return $this->request(
            'GET',
            '/project/current'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | PATH & VCS
    |--------------------------------------------------------------------------
    */

    public function path()
    {
        return $this->request(
            'GET',
            '/path'
        )->throw()->json();
    }

    public function vcs()
    {
        return $this->request(
            'GET',
            '/vcs'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | INSTANCE
    |--------------------------------------------------------------------------
    */

    public function instanceDispose()
    {
        return $this->request(
            'POST',
            '/instance/dispose'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | CONFIG
    |--------------------------------------------------------------------------
    */

    public function config()
    {
        return $this->request(
            'GET',
            '/config'
        )->throw()->json();
    }

    public function configProviders()
    {
        return $this->request(
            'GET',
            '/config/providers'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | PROVIDER
    |--------------------------------------------------------------------------
    */

    public function providers()
    {
        return $this->request(
            'GET',
            '/provider'
        )->throw()->json();
    }

    public function providerAuth()
    {
        return $this->request(
            'GET',
            '/provider/auth'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | AGENTS
    |--------------------------------------------------------------------------
    */

    public function agents()
    {
        return $this->request(
            'GET',
            '/agent'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | SESSION
    |--------------------------------------------------------------------------
    */

    public function sessions()
    {
        return $this->request(
            'GET',
            '/session'
        )->throw()->json();
    }

    public function createSession(
        ?string $title = null,
        ?string $parentID = null
    ) {
        $data = [];

        if ($title) {
            $data['title'] = $title;
        }

        if ($parentID) {
            $data['parentID'] = $parentID;
        }

        return $this->request(
            'POST',
            '/session',
            $data
        )->throw()->json();
    }

    public function getSession(
        string $sessionId
    ) {
        return $this->request(
            'GET',
            "/session/{$sessionId}"
        )->throw()->json();
    }

    public function updateSession(
        string $sessionId,
        ?string $title = null
    ) {
        return $this->request(
            'PATCH',
            "/session/{$sessionId}",
            [
                'title' => $title
            ]
        )->throw()->json();
    }

    public function deleteSession(
        string $sessionId
    ) {
        return $this->request(
            'DELETE',
            "/session/{$sessionId}"
        )->throw()->json();
    }

    public function sessionStatus()
    {
        return $this->request(
            'GET',
            '/session/status'
        )->throw()->json();
    }

    public function sessionChildren(
        string $sessionId
    ) {
        return $this->request(
            'GET',
            "/session/{$sessionId}/children"
        )->throw()->json();
    }

    public function sessionTodo(
        string $sessionId
    ) {
        return $this->request(
            'GET',
            "/session/{$sessionId}/todo"
        )->throw()->json();
    }

    public function forkSession(
        string $sessionId,
        ?string $messageID = null
    ) {
        return $this->request(
            'POST',
            "/session/{$sessionId}/fork",
            $messageID
                ? ['messageID' => $messageID]
                : []
        )->throw()->json();
    }

    public function abortSession(
        string $sessionId
    ) {
        return $this->request(
            'POST',
            "/session/{$sessionId}/abort"
        )->throw()->json();
    }

    public function shareSession(
        string $sessionId
    ) {
        return $this->request(
            'POST',
            "/session/{$sessionId}/share"
        )->throw()->json();
    }

    public function unshareSession(
        string $sessionId
    ) {
        return $this->request(
            'DELETE',
            "/session/{$sessionId}/share"
        )->throw()->json();
    }

    public function sessionDiff(
        string $sessionId
    ) {
        return $this->request(
            'GET',
            "/session/{$sessionId}/diff"
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | MESSAGE
    |--------------------------------------------------------------------------
    */

    public function messages(
        string $sessionId
    ) {
        return $this->request(
            'GET',
            "/session/{$sessionId}/message"
        )->throw()->json();
    }

    public function getMessage(
        string $sessionId,
        string $messageId
    ) {
        return $this->request(
            'GET',
            "/session/{$sessionId}/message/{$messageId}"
        )->throw()->json();
    }

    public function sendMessage(
        string $sessionId,
        string $message,
        string|array|null $model = null,
        ?string $agent = null
    ) {
        $data = [
            'parts' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];

        if (is_string($model) && str_contains($model, '/')) {
            [$providerID, $modelID] = explode('/', $model, 2);
            $data['model'] = compact('providerID', 'modelID');
        } elseif (is_array($model)) {
            $data['model'] = $model;
        }

        if ($agent) {
            $data['agent'] = $agent;
        }

        return $this->request(
            'POST',
            "/session/{$sessionId}/message",
            $data
        )->throw()->json();
    }

    public function promptAsync(
        string $sessionId,
        array $data
    ) {
        return $this->request(
            'POST',
            "/session/{$sessionId}/prompt_async",
            $data
        );
    }

    public function sessionCommand(
        string $sessionId,
        array $data
    ) {
        return $this->request(
            'POST',
            "/session/{$sessionId}/command",
            $data
        )->throw()->json();
    }

    public function sessionShell(
        string $sessionId,
        array $data
    ) {
        return $this->request(
            'POST',
            "/session/{$sessionId}/shell",
            $data
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | COMMAND
    |--------------------------------------------------------------------------
    */

    public function commands()
    {
        return $this->request(
            'GET',
            '/command'
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | FILE
    |--------------------------------------------------------------------------
    */

    public function files(
        ?string $path = null
    ) {
        $url = '/file';

        if ($path) {
            $url .= '?path=' . urlencode($path);
        }

        return $this->request(
            'GET',
            $url
        )->throw()->json();
    }

    public function fileContent(
        string $path
    ) {
        return $this->request(
            'GET',
            '/file/content?path=' .
            urlencode($path)
        )->throw()->json();
    }

    public function fileStatus()
    {
        return $this->request(
            'GET',
            '/file/status'
        )->throw()->json();
    }

    public function find(
        string $pattern
    ) {
        return $this->request(
            'GET',
            '/find?pattern=' .
            urlencode($pattern)
        )->throw()->json();
    }

    public function findFile(
        string $query
    ) {
        return $this->request(
            'GET',
            '/find/file?query=' .
            urlencode($query)
        )->throw()->json();
    }

    public function findSymbol(
        string $query
    ) {
        return $this->request(
            'GET',
            '/find/symbol?query=' .
            urlencode($query)
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | EXPERIMENTAL TOOLS
    |--------------------------------------------------------------------------
    */

    public function toolIds()
    {
        return $this->request(
            'GET',
            '/experimental/tool/ids'
        )->throw()->json();
    }

    public function tools(
        ?string $provider = null,
        ?string $model = null
    ) {
        $query = [];

        if ($provider) {
            $query['provider'] = $provider;
        }

        if ($model) {
            $query['model'] = $model;
        }

        return $this->request(
            'GET',
            '/experimental/tool',
            $query
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | LSP / FORMATTER / MCP
    |--------------------------------------------------------------------------
    */

    public function lsp()
    {
        return $this->request(
            'GET',
            '/lsp'
        )->throw()->json();
    }

    public function formatter()
    {
        return $this->request(
            'GET',
            '/formatter'
        )->throw()->json();
    }

    public function mcp()
    {
        return $this->request(
            'GET',
            '/mcp'
        )->throw()->json();
    }

    public function addMcp(
        array $data
    ) {
        return $this->request(
            'POST',
            '/mcp',
            $data
        )->throw()->json();
    }



    public function log(
        array $data
    ) {
        return $this->request(
            'POST',
            '/log',
            $data
        )->throw()->json();
    }


    /*
    |--------------------------------------------------------------------------
    | OPENAPI DOCUMENTATION
    |--------------------------------------------------------------------------
    */

    public function documentation()
    {
        return $this->request(
            'GET',
            '/doc'
        )->throw()->json();
    }
}