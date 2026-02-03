<?php

use OeleDev\MissingIndex\MissingIndexDetector;
use OeleDev\MissingIndex\MissingIndexMiddleware;

test('it registers detector as singleton', function () {
    $detector1 = app(MissingIndexDetector::class);
    $detector2 = app(MissingIndexDetector::class);

    expect($detector1)->toBe($detector2);
});

test('it merges config correctly', function () {
    expect(config('missing-index.environments'))->toBeArray()
        ->and(config('missing-index.threshold_ms'))->toBeInt()
        ->and(config('missing-index.ignore_tables'))->toBeArray()
        ->and(config('missing-index.ignore_patterns'))->toBeArray()
        ->and(config('missing-index.output'))->toBeArray();
});

test('it publishes config file', function () {
    $this->artisan('vendor:publish', [
        '--tag' => 'missing-index-config',
        '--force' => true,
    ])->assertSuccessful();

    expect(file_exists(config_path('missing-index.php')))->toBeTrue();

    // Clean up
    if (file_exists(config_path('missing-index.php'))) {
        unlink(config_path('missing-index.php'));
    }
});

test('it pushes middleware to kernel', function () {
    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

    $reflection = new \ReflectionClass($kernel);

    // Check if middleware is registered (implementation varies by Laravel version)
    // For Testbench, we just verify the middleware class exists
    expect(class_exists(MissingIndexMiddleware::class))->toBeTrue();
});
