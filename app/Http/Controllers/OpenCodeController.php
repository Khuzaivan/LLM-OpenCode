<?php

namespace App\Http\Controllers;

use App\Services\OpenCodeService;
use Illuminate\Http\Request;

class OpenCodeController extends Controller
{
    public function __construct(
        protected OpenCodeService $opencode
    ) {}

    public function index()
    {
        try {
            $health = $this->opencode->health();
            $providers = $this->opencode->providers();
            $agents = $this->opencode->agents();
            $sessions = $this->opencode->sessions();
            $mcp = [];
            try {
                $mcp = $this->opencode->mcp();
            } catch (\Throwable $mcpError) {
                // MCP is optional if server has no mcp
            }

            return view('opencode', compact(
                'health',
                'providers',
                'agents',
                'sessions',
                'mcp'
            ));
        } catch (\Throwable $e) {
            return view('opencode', [
                'health' => null,
                'providers' => [],
                'agents' => [],
                'sessions' => [],
                'mcp' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function createSession(Request $request)
    {
        $session = $this->opencode->createSession(
            $request->input('title', 'Laravel OpenCode Session')
        );

        return response()->json($session);
    }

    public function sendMessage(Request $request, string $sessionId)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'nullable',
            'agent' => 'nullable|string',
        ]);

        $response = $this->opencode->sendMessage(
            $sessionId,
            $request->message,
            $request->input('model'),
            $request->input('agent')
        );

        return response()->json($response);
    }

    public function messages(string $sessionId)
    {
        return response()->json(
            $this->opencode->messages($sessionId)
        );
    }

    public function deleteSession(string $sessionId)
    {
        return response()->json(
            $this->opencode->deleteSession($sessionId)
        );
    }
}