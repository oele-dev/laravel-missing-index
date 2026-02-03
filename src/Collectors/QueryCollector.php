<?php

namespace OeleDev\MissingIndex\Collectors;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryCollector
{
    protected Collection $queries;
    protected array $ignoreTables;
    protected array $ignorePatterns;

    public function __construct(array $ignoreTables = [], array $ignorePatterns = [])
    {
        $this->queries = new Collection();
        $this->ignoreTables = $ignoreTables;
        $this->ignorePatterns = $ignorePatterns;
    }

    public function start(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if ($this->shouldIgnore($query->sql)) {
                return;
            }

            $this->queries->push([
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ]);
        });
    }

    public function getQueries(): Collection
    {
        return $this->queries;
    }

    public function reset(): void
    {
        $this->queries = new Collection();
    }

    protected function shouldIgnore(string $sql): bool
    {
        // Only analyze SELECT queries
        if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
            return true;
        }

        // Check ignored tables
        foreach ($this->ignoreTables as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $sql)) {
                return true;
            }
        }

        // Check ignored patterns
        foreach ($this->ignorePatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }
}
