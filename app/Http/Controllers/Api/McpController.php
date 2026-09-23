<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\McpDatabaseBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MCP Database Controller
 *
 * REST endpoints for the MCP bridge, exposing MySQL database
 * as MCP resources/tools for OpenCode integration.
 */
class McpController extends Controller
{
    private McpDatabaseBridge $bridge;

    public function __construct(McpDatabaseBridge $bridge)
    {
        $this->bridge = $bridge;
    }

    /**
     * List all MCP database resources (tables).
     * GET /api/mcp/database/resources
     */
    public function listResources(): JsonResponse
    {
        try {
            $resources = $this->bridge->listResources();

            return response()->json([
                'status' => 'ok',
                'data' => $resources,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Read data from a specific MCP resource.
     * GET /api/mcp/database/resource/{uri}
     */
    public function readResource(Request $request, string $uri): JsonResponse
    {
        try {
            $limit = (int) $request->input('limit', 50);
            $offset = (int) $request->input('offset', 0);

            $data = $this->bridge->readResource($uri, $limit, $offset);

            return response()->json([
                'status' => 'ok',
                'data' => $data,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
    * Search students via MCP.
     * GET /api/mcp/database/search?q={query}
     */
    public function searchStudents(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $limit = (int) $request->input('limit', 20);

            if (empty($query)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Search query parameter "q" is required.',
                ], 422);
            }

            $results = $this->bridge->searchStudents($query, $limit);

            return response()->json([
                'status' => 'ok',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get table schema.
     * GET /api/mcp/database/schema/{table}
     */
    public function getSchema(string $table): JsonResponse
    {
        try {
            $schema = $this->bridge->getSchema($table);

            return response()->json([
                'status' => 'ok',
                'data' => $schema,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute read-only SQL query via MCP.
     * POST /api/mcp/database/query
     */
    public function executeQuery(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string',
            ]);

            $results = $this->bridge->executeQuery($validated['query']);

            return response()->json([
                'status' => 'ok',
                'data' => $results,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get MCP context for LLM consumption.
     * GET /api/mcp/database/context?q={optional_query}
     */
    public function getContext(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');
            $context = $this->bridge->getContext($query);

            return response()->json([
                'status' => 'ok',
                'data' => [
                    'context' => $context,
                    'query' => $query,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
