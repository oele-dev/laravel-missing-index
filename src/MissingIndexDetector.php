<?php

namespace OeleDev\MissingIndex;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use OeleDev\MissingIndex\Analyzers\ExplainAnalyzer;
use OeleDev\MissingIndex\Collectors\QueryCollector;
use OeleDev\MissingIndex\Reports\MissingIndexReport;

class MissingIndexDetector
{
    protected QueryCollector $collector;
    protected ExplainAnalyzer $analyzer;
    protected Collection $detectedIssues;
    protected array $config;

    public function __construct()
    {
        $this->config = config('missing-index', []);
        $this->collector = new QueryCollector(
            $this->config['ignore_tables'] ?? [],
            $this->config['ignore_patterns'] ?? []
        );
        $this->analyzer = new ExplainAnalyzer();
        $this->detectedIssues = new Collection();
    }

    public function boot(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $this->collector->start();
    }

    public function isEnabled(): bool
    {
        $enabled = $this->config['enabled'] ?? null;

        // If enabled is null, use app.debug
        if ($enabled === null) {
            $enabled = config('app.debug', false);
        }

        if (!$enabled) {
            return false;
        }

        // Check environment
        $environments = $this->config['environments'] ?? [];
        if (!empty($environments) && !in_array(App::environment(), $environments)) {
            return false;
        }

        return true;
    }

    public function analyze(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $thresholdMs = $this->config['threshold_ms'] ?? 0;
        $queries = $this->collector->getQueries();

        // Use hash to deduplicate queries within same request
        $analyzed = [];

        foreach ($queries as $query) {
            // Filter by threshold
            if ($query['time'] < $thresholdMs) {
                continue;
            }

            // Deduplicate by SQL hash
            $hash = md5($query['sql']);
            if (isset($analyzed[$hash])) {
                continue;
            }
            $analyzed[$hash] = true;

            $report = $this->analyzer->analyze(
                $query['sql'],
                $query['bindings'],
                $query['connection'],
                $query['time']
            );

            if ($report !== null) {
                $this->detectedIssues->push($report);
            }
        }
    }

    public function getDetectedIssues(): Collection
    {
        return $this->detectedIssues;
    }

    public function output($response): void
    {
        if (!$this->isEnabled() || $this->detectedIssues->isEmpty()) {
            return;
        }

        $outputHandlers = $this->config['output'] ?? [];

        foreach ($outputHandlers as $handlerClass) {
            if (class_exists($handlerClass)) {
                $handler = app($handlerClass);
                $handler->boot();
                $handler->output($this->detectedIssues, $response);
            }
        }
    }

    public function reset(): void
    {
        $this->collector->reset();
        $this->detectedIssues = new Collection();
    }
}
