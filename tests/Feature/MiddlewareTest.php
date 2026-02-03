<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use OeleDev\MissingIndex\MissingIndexDetector;
use OeleDev\MissingIndex\MissingIndexMiddleware;
use OeleDev\MissingIndex\Tests\Fixtures\ExplainFixtures;

beforeEach(function () {
    config(['missing-index.enabled' => true]);
    config(['missing-index.environments' => []]);
    config(['missing-index.output' => []]);
});

test('it boots detector on request', function () {
    $detector = Mockery::mock(MissingIndexDetector::class);
    $detector->shouldReceive('boot')->once();
    $detector->shouldReceive('analyze')->once();
    $detector->shouldReceive('output')->once();

    $middleware = new MissingIndexMiddleware($detector);

    $request = Request::create('/test', 'GET');
    $response = new Response('test');

    $result = $middleware->handle($request, function ($req) use ($response) {
        return $response;
    });

    expect($result)->toBe($response);
});

test('it analyzes after response', function () {
    $detector = app(MissingIndexDetector::class);
    $middleware = new MissingIndexMiddleware($detector);

    $request = Request::create('/test', 'GET');

    $middleware->handle($request, function ($req) {
        // Simulate a query during the request
        DB::select('SELECT * FROM users');
        return new Response('test');
    });

    // Verify detector collected queries (queries collection should not be empty)
    $reflection = new \ReflectionClass($detector);
    $collectorProperty = $reflection->getProperty('collector');
    $collectorProperty->setAccessible(true);
    $collector = $collectorProperty->getValue($detector);

    expect($collector->getQueries())->not->toBeEmpty();
});

test('it passes response through unchanged', function () {
    $detector = app(MissingIndexDetector::class);
    $middleware = new MissingIndexMiddleware($detector);

    $request = Request::create('/test', 'GET');
    $expectedResponse = new Response('test content', 200, ['X-Custom' => 'header']);

    $actualResponse = $middleware->handle($request, function ($req) use ($expectedResponse) {
        return $expectedResponse;
    });

    expect($actualResponse)->toBe($expectedResponse)
        ->and($actualResponse->getContent())->toBe('test content')
        ->and($actualResponse->headers->get('X-Custom'))->toBe('header');
});

test('it integrates with full request lifecycle', function () {
    config(['missing-index.enabled' => true]);

    Route::get('/test-route', function () {
        DB::select('SELECT * FROM users WHERE id = ?', [1]);
        return response()->json(['status' => 'ok']);
    });

    $response = $this->get('/test-route');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});

test('it does not interfere with exceptions', function () {
    $detector = app(MissingIndexDetector::class);
    $middleware = new MissingIndexMiddleware($detector);

    $request = Request::create('/test', 'GET');

    try {
        $middleware->handle($request, function ($req) {
            throw new \Exception('Test exception');
        });
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('Test exception');
    }
});
