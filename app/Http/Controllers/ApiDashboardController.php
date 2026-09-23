<?php

namespace App\Http\Controllers;

use App\Services\OpenCodeService;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    public function index(OpenCodeService $opencode)
    {
        $data = [];

        $apis = [
            'Global Health' => '/global/health',
            'Projects' => '/project',
            'Current Project' => '/project/current',
            'Path' => '/path',
            'VCS' => '/vcs',
            'Config' => '/config',
            'Config Providers' => '/config/providers',
            'Providers' => '/provider',
            'Provider Auth' => '/provider/auth',
            'Agents' => '/agent',
            'Sessions' => '/session',
            'Session Status' => '/session/status',
            'Commands' => '/command',
            'Files' => '/file',
            'File Status' => '/file/status',
            'LSP' => '/lsp',
            'Formatter' => '/formatter',
            'MCP' => '/mcp',
            'Tool IDs' => '/experimental/tool/ids',
        ];

        return view(
            'api-dashboard',
            compact('apis')
        );
    }

    public function call(
        Request $request,
        OpenCodeService $opencode
    ) {
        $request->validate([
            'method' => 'required|in:GET,POST,PATCH,PUT,DELETE',
            'endpoint' => 'required|string'
        ]);

        try {

            $response = $opencode->request(
                $request->method,
                $request->endpoint,
                $request->input('data', [])
            );

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json()
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}