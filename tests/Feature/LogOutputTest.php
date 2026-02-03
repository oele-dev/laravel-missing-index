<?php

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log as LaravelLog;
use OeleDev\MissingIndex\Outputs\Log;
use OeleDev\MissingIndex\Reports\MissingIndexReport;

beforeEach(function () {
    config(['missing-index.log_channel' => 'stack']);
});

test('it logs warning when issues detected', function () {
    LaravelLog::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->shouldReceive('warning')
        ->twice(); // Once for summary, once for individual issue

    $log = new Log();
    $issues = new Collection([
        new MissingIndexReport(
            sql: 'SELECT * FROM users WHERE email = ?',
            table: 'users',
            columns: ['email'],
            type: 'ALL',
            key: null,
            rows: 10000,
            extra: 'Using filesort',
            suggestion: 'ALTER TABLE `users` ADD INDEX `idx_users_email` (email)',
            executionTime: 150.5
        ),
    ]);

    $log->output($issues, new Response());

    expect(true)->toBeTrue();
});

test('it skips logging when no issues', function () {
    LaravelLog::shouldReceive('channel')
        ->never();

    $log = new Log();
    $issues = new Collection([]);

    $log->output($issues, new Response());

    expect(true)->toBeTrue();
});

test('it uses configured log channel', function () {
    config(['missing-index.log_channel' => 'custom-channel']);

    LaravelLog::shouldReceive('channel')
        ->with('custom-channel')
        ->andReturnSelf()
        ->shouldReceive('warning')
        ->twice();

    $log = new Log('custom-channel');
    $issues = new Collection([
        new MissingIndexReport(
            sql: 'SELECT * FROM users',
            table: 'users',
            columns: [],
            type: 'ALL',
            key: null,
            rows: 5000,
            extra: '',
            suggestion: 'ALTER TABLE users ADD INDEX',
            executionTime: 100.0
        ),
    ]);

    $log->output($issues, new Response());

    expect(true)->toBeTrue();
});

test('it includes request context', function () {
    $contextMatcher = Mockery::on(function ($context) {
        return isset($context['count'])
            && isset($context['url'])
            && isset($context['method']);
    });

    LaravelLog::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->shouldReceive('warning')
        ->with('Missing Database Indexes Detected', $contextMatcher)
        ->once()
        ->shouldReceive('warning')
        ->once(); // For the individual issue

    request()->merge(['test' => 'value']);

    $log = new Log();
    $issues = new Collection([
        new MissingIndexReport(
            sql: 'SELECT * FROM users',
            table: 'users',
            columns: ['email'],
            type: 'ALL',
            key: null,
            rows: 1000,
            extra: '',
            suggestion: 'ALTER TABLE users ADD INDEX',
            executionTime: 50.0
        ),
    ]);

    $log->output($issues, new Response());

    expect(true)->toBeTrue();
});

test('it logs multiple issues', function () {
    LaravelLog::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->shouldReceive('warning')
        ->times(3); // Once for summary, twice for individual issues

    $log = new Log();
    $issues = new Collection([
        new MissingIndexReport(
            sql: 'SELECT * FROM users WHERE email = ?',
            table: 'users',
            columns: ['email'],
            type: 'ALL',
            key: null,
            rows: 10000,
            extra: '',
            suggestion: 'ALTER TABLE users ADD INDEX',
            executionTime: 100.0
        ),
        new MissingIndexReport(
            sql: 'SELECT * FROM posts WHERE user_id = ?',
            table: 'posts',
            columns: ['user_id'],
            type: 'ALL',
            key: null,
            rows: 5000,
            extra: 'Using filesort',
            suggestion: 'ALTER TABLE posts ADD INDEX',
            executionTime: 75.0
        ),
    ]);

    $log->output($issues, new Response());

    expect(true)->toBeTrue();
});

test('it includes all report fields in log context', function () {
    $summaryContext = null;
    $detailContext = null;

    LaravelLog::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->shouldReceive('warning')
        ->with('Missing Database Indexes Detected', Mockery::capture($summaryContext))
        ->once()
        ->shouldReceive('warning')
        ->with(Mockery::type('string'), Mockery::capture($detailContext))
        ->once();

    $log = new Log();
    $issues = new Collection([
        new MissingIndexReport(
            sql: 'SELECT * FROM users WHERE email = ?',
            table: 'users',
            columns: ['email', 'status'],
            type: 'ALL',
            key: null,
            rows: 10000,
            extra: 'Using filesort',
            suggestion: 'ALTER TABLE users ADD INDEX',
            executionTime: 150.5
        ),
    ]);

    $log->output($issues, new Response());

    // Verify all fields are present
    expect($detailContext)->toHaveKey('table')
        ->and($detailContext)->toHaveKey('columns')
        ->and($detailContext)->toHaveKey('type')
        ->and($detailContext)->toHaveKey('key')
        ->and($detailContext)->toHaveKey('rows')
        ->and($detailContext)->toHaveKey('extra')
        ->and($detailContext)->toHaveKey('execution_time_ms')
        ->and($detailContext)->toHaveKey('suggestion')
        ->and($detailContext)->toHaveKey('sql');
});
