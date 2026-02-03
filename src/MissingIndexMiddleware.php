<?php

namespace OeleDev\MissingIndex;

use Closure;
use Illuminate\Http\Request;

class MissingIndexMiddleware
{
    protected MissingIndexDetector $detector;

    public function __construct(MissingIndexDetector $detector)
    {
        $this->detector = $detector;
    }

    public function handle(Request $request, Closure $next)
    {
        $this->detector->boot();

        $response = $next($request);

        $this->detector->analyze();
        $this->detector->output($response);

        return $response;
    }
}
