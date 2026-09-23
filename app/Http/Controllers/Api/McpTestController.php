<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\McpDatabaseBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MCP Test Controller
 *
 * Dedicated testing endpoints to verify that OpenCode accurately
 * fetches and parses mock data from MySQL through the MCP connection.
 */
class McpTestController extends Controller
{
    private McpDatabaseBridge $bridge;

    public function __construct(McpDatabaseBridge $bridge)
    {
        $this->bridge = $bridge;
    }

    /**
     * Test MCP connection and database status.
     * GET /api/test/mcp-connection
     *
     * Verifies:
     * - MySQL connection is active
    * - students table exists
    * - students table has data
     * - MCP bridge is functional
     */
    public function testConnection(): JsonResponse
    {
        $checks = [];

        // 1. Test MySQL connection
        try {
            DB::connection('mysql')->getPdo();
            $checks['mysql_connection'] = [
                'status' => 'ok',
                'driver' => config('database.connections.mysql.driver'),
                'database' => config('database.connections.mysql.database'),
                'host' => config('database.connections.mysql.host'),
            ];
        } catch (\Exception $e) {
            $checks['mysql_connection'] = [
                'status' => 'error',
                'message' => 'MySQL connection failed: ' . $e->getMessage(),
            ];

            return response()->json([
                'status' => 'error',
                'message' => 'MySQL connection failed',
                'checks' => $checks,
            ], 500);
        }

        // 2. Check if students table exists
        try {
            $tableExists = Schema::connection('mysql')->hasTable('students');
            $checks['students_table'] = [
                'status' => $tableExists ? 'ok' : 'error',
                'exists' => $tableExists,
                'message' => $tableExists
                    ? 'Tabel students ditemukan'
                    : 'Tabel students belum tersedia di mcp_demo_db.',
            ];
        } catch (\Exception $e) {
            $checks['students_table'] = [
                'status' => 'error',
                'message' => 'Failed to check table: ' . $e->getMessage(),
            ];
        }

        // 3. Check if students table has data
        try {
            if ($tableExists) {
                $count = DB::connection('mysql')->table('students')->count();
                $checks['students_data'] = [
                    'status' => $count > 0 ? 'ok' : 'warning',
                    'count' => $count,
                    'message' => $count > 0
                        ? "{$count} student ditemukan di database"
                        : 'Tabel students kosong.',
                ];
            }
        } catch (\Exception $e) {
            $checks['students_data'] = [
                'status' => 'error',
                'message' => 'Failed to count students: ' . $e->getMessage(),
            ];
        }

        // 4. Test MCP Bridge listResources
        try {
            $resources = $this->bridge->listResources();
            $checks['mcp_bridge'] = [
                'status' => 'ok',
                'resources_count' => count($resources),
                'message' => count($resources) . ' MCP resources tersedia',
            ];
        } catch (\Exception $e) {
            $checks['mcp_bridge'] = [
                'status' => 'error',
                'message' => 'MCP Bridge error: ' . $e->getMessage(),
            ];
        }

        // 5. Test MCP context generation
        try {
            $context = $this->bridge->getContext(null);
            $checks['mcp_context'] = [
                'status' => !empty($context) ? 'ok' : 'error',
                'context_length' => strlen($context),
                'message' => 'MCP context berhasil di-generate (' . strlen($context) . ' chars)',
            ];
        } catch (\Exception $e) {
            $checks['mcp_context'] = [
                'status' => 'error',
                'message' => 'MCP context generation failed: ' . $e->getMessage(),
            ];
        }

        // Overall status
        $allOk = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $allOk ? 'ok' : 'partial',
            'message' => $allOk
                ? '✓ Semua tes MCP berhasil! Koneksi database dan bridge berfungsi dengan baik.'
                : '⚠ Beberapa tes memiliki masalah, lihat detail di bawah.',
            'checks' => $checks,
        ]);
    }

    /**
     * Test MCP search functionality.
    * GET /api/test/mcp-search?q={query}
     */
    public function testSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', 'machine learning');

        try {
            $startTime = microtime(true);
            $results = $this->bridge->searchStudents($query);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'status' => 'ok',
                'message' => "Pencarian '{$query}' mengembalikan {$results['total']} hasil dalam {$duration}ms",
                'data' => $results,
                'performance' => [
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search test failed: ' . $e->getMessage(),
                'query' => $query,
            ], 500);
        }
    }

    /**
     * Test MCP schema retrieval.
     * GET /api/test/mcp-schema
     */
    public function testSchema(): JsonResponse
    {
        try {
            $schema = $this->bridge->getSchema('students');

            return response()->json([
                'status' => 'ok',
                'message' => '✓ Skema tabel students berhasil diambil',
                'data' => $schema,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schema test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test MCP read-only query execution.
     * POST /api/test/mcp-query
     */
    public function testQuery(Request $request): JsonResponse
    {
        $query = $request->input('query', 'SELECT id, nim, name, major, semester, gpa FROM students LIMIT 5');

        try {
            $startTime = microtime(true);
            $results = $this->bridge->executeQuery($query);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'status' => 'ok',
                'message' => "✓ Query berhasil dijalankan, mengembalikan {$results['total']} baris dalam {$duration}ms",
                'data' => $results,
                'performance' => [
                    'duration_ms' => $duration,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Security violation: ' . $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Query test failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
