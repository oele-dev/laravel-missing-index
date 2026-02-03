<?php

namespace OeleDev\MissingIndex\Outputs;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log as LaravelLog;
use OeleDev\MissingIndex\Reports\MissingIndexReport;

class Log implements Output
{
    protected string $channel;

    public function __construct(?string $channel = null)
    {
        $this->channel = $channel ?? config('missing-index.log_channel', 'daily');
    }

    public function boot(): void
    {
        // No boot logic needed for log output
    }

    public function output(Collection $detectedIssues, Response $response): void
    {
        if ($detectedIssues->isEmpty()) {
            return;
        }

        $logger = LaravelLog::channel($this->channel);

        $logger->warning('Missing Database Indexes Detected', [
            'count' => $detectedIssues->count(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);

        /** @var MissingIndexReport $issue */
        foreach ($detectedIssues as $issue) {
            $logger->warning('Missing Index on ' . $issue->table, [
                'table' => $issue->table,
                'columns' => $issue->columns,
                'type' => $issue->type,
                'key' => $issue->key,
                'rows' => $issue->rows,
                'extra' => $issue->extra,
                'execution_time_ms' => round($issue->executionTime, 2),
                'suggestion' => $issue->suggestion,
                'sql' => $issue->sql,
            ]);
        }
    }
}
