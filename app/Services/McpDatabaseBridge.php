<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MCP Database Bridge
 *
 * Exposes the local MySQL database as MCP (Model Context Protocol) resources/tools,
 * allowing OpenCode to read, search, and contextually query the database.
 */
class McpDatabaseBridge
{
    /**
    * Expose only the students table as an MCP resource.
     *
     * @return array
     */
    public function listResources(): array
    {
        $this->ensureStudentsTable();
        $count = DB::connection('mysql')->table('students')->count();

        return [[
            'uri' => 'mysql://students',
            'name' => 'students',
            'description' => "MySQL table 'students' with {$count} records",
            'mimeType' => 'application/json',
            'recordCount' => $count,
        ]];
    }

    /**
     * Read data from a specific MCP resource (table).
     *
    * @param string $uri MCP resource URI (e.g., "mysql://students")
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function readResource(string $uri, int $limit = 50, int $offset = 0): array
    {
        $table = $this->parseTableFromUri($uri);
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $this->ensureStudentsTable();
        if ($table !== 'students') {
            throw new \InvalidArgumentException("Only the 'students' MCP resource is available.");
        }

        $connection = DB::connection('mysql');
        $data = $connection->table('students')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $total = $connection->table('students')->count();

        return [
            'uri' => $uri,
            'table' => $table,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'hasMore' => ($offset + $limit) < $total,
            ],
        ];
    }

    /**
    * Search students by NIM, name, major, semester, or GPA.
     *
     * @param string $query
     * @param int    $limit
     * @return array
     */
    public function searchStudents(string $query, int $limit = 20): array
    {
        $this->ensureStudentsTable();
        $limit = max(1, min($limit, 100));
        $pattern = '%' . addcslashes($query, '%_\\') . '%';
        $results = DB::connection('mysql')->table('students')
            ->where('nim', 'LIKE', $pattern)
            ->orWhere('name', 'LIKE', $pattern)
            ->orWhere('major', 'LIKE', $pattern)
            ->orWhereRaw('CAST(semester AS CHAR) LIKE ?', [$pattern])
            ->orWhereRaw('CAST(gpa AS CHAR) LIKE ?', [$pattern])
            ->limit($limit)
            ->get();

        return [
            'query' => $query,
            'total' => $results->count(),
            'results' => $results->all(),
        ];
    }

    /**
     * Execute a read-only SQL query.
     * Only SELECT statements are allowed for security.
     *
     * @param string $query
     * @return array
     */
    public function executeQuery(string $query): array
    {
        // Security: only allow SELECT queries
        $normalizedQuery = trim($query);
        if (!preg_match('/^SELECT\b/i', $normalizedQuery)) {
            throw new \InvalidArgumentException('Only SELECT queries are allowed for security reasons.');
        }

        if (str_contains($normalizedQuery, ';')) {
            throw new \InvalidArgumentException('Multiple SQL statements are not allowed.');
        }

        try {
            $results = DB::connection('mysql')->select($query);
            return [
                'query' => $query,
                'total' => count($results),
                'results' => $results,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Query execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the schema of a specific table.
     *
     * @param string $table
     * @return array
     */
    public function getSchema(string $table): array
    {
        $this->ensureStudentsTable();
        if ($table !== 'students') {
            throw new \InvalidArgumentException("Only the 'students' table is available.");
        }

        $schema = Schema::connection('mysql');
        $columns = $schema->getColumns($table);
        $indexes = $schema->getIndexes($table);

        return [
            'table' => $table,
            'columns' => $columns,
            'indexes' => $indexes,
            'recordCount' => DB::connection('mysql')->table('students')->count(),
        ];
    }

    /**
     * Get MCP context string for LLM consumption.
     * Combines schema info and search results into a formatted context.
     *
     * @param string|null $query Search query (optional)
     * @return string
     */
    public function getContext(?string $query = null): string
    {
        $context = "=== MCP Database Context ===\n";
        $context .= "Database: " . DB::connection('mysql')->getDatabaseName() . "\n";
        $context .= "Connection: MySQL\n\n";

        $schema = $this->getSchema('students');
        $context .= "--- Table: students ---\n";
        $context .= "Records: {$schema['recordCount']}\n";
        $context .= "Columns:\n";
        foreach ($schema['columns'] as $col) {
            $nullable = ($col['nullable'] ?? false) ? ', nullable' : '';
            $context .= "  - {$col['name']} ({$col['type_name']}{$nullable})\n";
        }
        $context .= "\n";

        $students = DB::connection('mysql')->table('students')
            ->select(['id', 'nim', 'name', 'major', 'semester', 'gpa'])
            ->orderBy('id')
            ->limit(50)
            ->get();

        $context .= "--- Student Records ---\n";
        $context .= "Showing {$students->count()} of {$schema['recordCount']} students:\n";
        foreach ($students as $student) {
            $context .= "- ID: {$student->id}; NIM: {$student->nim}; Name: {$student->name}; ";
            $context .= "Major: {$student->major}; Semester: {$student->semester}; GPA: {$student->gpa}\n";
        }
        $context .= "\n";

        if ($query) {
            $searchResults = $this->searchStudents($query, 10);
            $context .= "--- Search Results for: \"{$query}\" ---\n";
            $context .= "Found: {$searchResults['total']} students\n\n";

            foreach ($searchResults['results'] as $student) {
                $context .= "Student #{$student->id}: {$student->name}\n";
                $context .= "  NIM: {$student->nim}\n";
                $context .= "  Major: {$student->major}\n";
                $context .= "  Semester: {$student->semester}\n";
                $context .= "  GPA: {$student->gpa}\n\n";
            }
        }

        $context .= "=== End MCP Context ===\n";

        return $context;
    }

    /**
     * Parse table name from MCP URI.
     *
    * @param string $uri e.g., "mysql://students"
     * @return string
     */
    private function parseTableFromUri(string $uri): string
    {
        // Support formats: "mysql://table_name" or just "table_name"
        if (str_starts_with($uri, 'mysql://')) {
            return substr($uri, 8);
        }

        return $uri;
    }

    private function ensureStudentsTable(): void
    {
        if (!Schema::connection('mysql')->hasTable('students')) {
            throw new \RuntimeException("Required MySQL table 'students' does not exist.");
        }
    }
}
