<?php

namespace OeleDev\MissingIndex\Analyzers;

use Illuminate\Support\Facades\DB;
use OeleDev\MissingIndex\Reports\MissingIndexReport;

class ExplainAnalyzer
{
    protected int $rowsThreshold = 1000;

    public function analyze(string $sql, array $bindings, string $connection, float $executionTime): ?MissingIndexReport
    {
        try {
            $explainResults = DB::connection($connection)
                ->select("EXPLAIN {$sql}", $bindings);

            if (empty($explainResults)) {
                return null;
            }

            // Analyze first row (primary table)
            $explainRow = (array) $explainResults[0];
            $explainData = $this->parseExplainOutput($explainRow);

            if (!$this->shouldWarn($explainData)) {
                return null;
            }

            $columns = $this->extractColumns($sql);
            $suggestion = $this->generateSuggestion($explainData['table'], $columns);

            return new MissingIndexReport(
                sql: $sql,
                table: $explainData['table'],
                columns: $columns,
                type: $explainData['type'],
                key: $explainData['key'],
                rows: $explainData['rows'],
                extra: $explainData['extra'],
                suggestion: $suggestion,
                executionTime: $executionTime
            );
        } catch (\Exception $e) {
            // Silently fail - don't break the request
            // Could log this in debug mode if needed
            return null;
        }
    }

    protected function parseExplainOutput(array $explainRow): array
    {
        return [
            'table' => $explainRow['table'] ?? '',
            'type' => $explainRow['type'] ?? '',
            'key' => $explainRow['key'] ?? null,
            'rows' => (int) ($explainRow['rows'] ?? 0),
            'extra' => $explainRow['Extra'] ?? '',
        ];
    }

    protected function shouldWarn(array $explainData): bool
    {
        // Full table scan
        if ($explainData['type'] === 'ALL') {
            return true;
        }

        // No index used
        if ($explainData['key'] === null) {
            return true;
        }

        // High row count without proper index
        if ($explainData['rows'] > $this->rowsThreshold && $explainData['key'] === null) {
            return true;
        }

        // Using filesort or temporary table (performance issues)
        $extra = strtolower($explainData['extra']);
        if (str_contains($extra, 'using filesort') || str_contains($extra, 'using temporary')) {
            return true;
        }

        return false;
    }

    protected function extractColumns(string $sql): array
    {
        $columns = [];

        // Extract WHERE clause columns
        if (preg_match_all('/WHERE\s+.*?(?:AND|OR)?\s*`?(\w+)`?\s*[=<>]/i', $sql, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }

        // Extract JOIN columns
        if (preg_match_all('/JOIN\s+.*?ON\s+.*?`?(\w+)`?\s*=/i', $sql, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }

        // Extract ORDER BY columns
        if (preg_match_all('/ORDER\s+BY\s+`?(\w+)`?/i', $sql, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }

        return array_unique($columns);
    }

    protected function generateSuggestion(string $table, array $columns): string
    {
        if (empty($columns)) {
            return "Consider adding an index to table '{$table}'";
        }

        $columnList = implode(', ', $columns);
        $indexName = 'idx_' . $table . '_' . implode('_', array_slice($columns, 0, 3));

        return "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columnList})";
    }
}
