<?php

namespace OeleDev\MissingIndex\Outputs;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

interface Output
{
    /**
     * Boot the output handler.
     */
    public function boot(): void;

    /**
     * Output the detected missing index issues.
     *
     * @param Collection $detectedIssues Collection of MissingIndexReport objects
     * @param Response $response
     */
    public function output(Collection $detectedIssues, Response $response): void;
}
