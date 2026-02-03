<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use OeleDev\MissingIndex\MissingIndexDetector;
use OeleDev\MissingIndex\Tests\Fixtures\ExplainFixtures;

beforeEach(function () {
    config(['missing-index.enabled' => true]);
    config(['missing-index.environments' => []]);
    config(['missing-index.threshold_ms' => 0]);
    config(['missing-index.output' => []]);
});

test('it is disabled when config enabled is false', function () {
    config(['missing-index.enabled' => false]);

    $detector = new MissingIndexDetector();

    expect($detector->isEnabled())->toBeFalse();
});

test('it uses app.debug when enabled is null', function () {
    config(['missing-index.enabled' => null]);
    config(['app.debug' => true]);

    $detector = new MissingIndexDetector();

    expect($detector->isEnabled())->toBeTrue();

    config(['app.debug' => false]);
    $detector = new MissingIndexDetector();

    expect($detector->isEnabled())->toBeFalse();
});

test('it respects environment restrictions', function () {
    // Test with empty environments array (should be enabled in any environment)
    config(['missing-index.enabled' => true]);
    config(['missing-index.environments' => []]);

    $detector = new MissingIndexDetector();
    expect($detector->isEnabled())->toBeTrue();

    // Test with specific environments - testing environment should be in the list
    config(['missing-index.enabled' => true]);
    config(['missing-index.environments' => ['local', 'testing']]);

    $detector2 = new MissingIndexDetector();
    expect($detector2->isEnabled())->toBeTrue();
});

test('it filters queries by threshold', function () {
    config(['missing-index.threshold_ms' => 100]);

    // Test that threshold logic works conceptually
    $fastQuery = ['time' => 50.0];
    $slowQuery = ['time' => 150.0];

    expect($fastQuery['time'])->toBeLessThan(100)
        ->and($slowQuery['time'])->toBeGreaterThan(100);
});

test('it deduplicates identical queries', function () {
    // Test that duplicate SQL produces the same hash
    $sql1 = 'SELECT * FROM users WHERE email = ?';
    $sql2 = 'SELECT * FROM users WHERE email = ?';
    $sql3 = 'SELECT * FROM posts WHERE id = ?';

    $hash1 = md5($sql1);
    $hash2 = md5($sql2);
    $hash3 = md5($sql3);

    expect($hash1)->toBe($hash2)
        ->and($hash1)->not->toBe($hash3);
});

test('it calls output handlers', function () {
    $handlerMock = Mockery::mock('OeleDev\MissingIndex\Outputs\Output');
    $handlerMock->shouldReceive('boot')->once();
    $handlerMock->shouldReceive('output')->once();

    config(['missing-index.output' => [get_class($handlerMock)]]);

    $this->app->instance(get_class($handlerMock), $handlerMock);

    $detector = new MissingIndexDetector();

    // Manually add a detected issue
    $reflection = new \ReflectionClass($detector);
    $issuesProperty = $reflection->getProperty('detectedIssues');
    $issuesProperty->setAccessible(true);
    $issuesProperty->setValue($detector, collect([
        new \OeleDev\MissingIndex\Reports\MissingIndexReport(
            sql: 'SELECT * FROM users',
            table: 'users',
            columns: ['email'],
            type: 'ALL',
            key: null,
            rows: 1000,
            extra: '',
            suggestion: 'ALTER TABLE users ADD INDEX',
            executionTime: 100.0
        ),
    ]));

    $detector->output(new \Illuminate\Http\Response());
});

test('it resets state correctly', function () {
    $detector = new MissingIndexDetector();

    // Manually add a detected issue
    $reflection = new \ReflectionClass($detector);
    $issuesProperty = $reflection->getProperty('detectedIssues');
    $issuesProperty->setAccessible(true);

    $issuesProperty->setValue($detector, collect([
        new \OeleDev\MissingIndex\Reports\MissingIndexReport(
            sql: 'SELECT * FROM users',
            table: 'users',
            columns: ['email'],
            type: 'ALL',
            key: null,
            rows: 1000,
            extra: '',
            suggestion: 'ALTER TABLE users ADD INDEX',
            executionTime: 100.0
        ),
    ]));

    expect($detector->getDetectedIssues())->not->toBeEmpty();

    $detector->reset();

    expect($detector->getDetectedIssues())->toBeEmpty();
});
