<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use OeleDev\MissingIndex\Collectors\QueryCollector;

test('it collects SELECT queries', function () {
    $collector = new QueryCollector();
    $collector->start();

    DB::select('SELECT * FROM users WHERE email = ?', ['test@example.com']);

    $queries = $collector->getQueries();
    expect($queries)->toHaveCount(1)
        ->and($queries->first()['sql'])->toContain('SELECT * FROM users')
        ->and($queries->first()['bindings'])->toBe(['test@example.com'])
        ->and($queries->first()['connection'])->toBe('sqlite');
});

test('it ignores INSERT queries', function () {
    $collector = new QueryCollector();

    // Test the shouldIgnore method directly via reflection
    $reflection = new \ReflectionClass($collector);
    $method = $reflection->getMethod('shouldIgnore');
    $method->setAccessible(true);

    expect($method->invoke($collector, 'INSERT INTO users (name, email) VALUES (?, ?)'))->toBeTrue();
});

test('it ignores UPDATE queries', function () {
    $collector = new QueryCollector();
    $collector->start();

    DB::update('UPDATE users SET name = ? WHERE id = ?', ['Jane', 1]);

    expect($collector->getQueries())->toHaveCount(0);
});

test('it ignores DELETE queries', function () {
    $collector = new QueryCollector();
    $collector->start();

    DB::delete('DELETE FROM users WHERE id = ?', [1]);

    expect($collector->getQueries())->toHaveCount(0);
});

test('it ignores configured tables', function () {
    $collector = new QueryCollector(['migrations', 'sessions']);
    $collector->start();

    DB::select('SELECT * FROM migrations');
    DB::select('SELECT * FROM sessions WHERE id = ?', ['abc']);
    DB::select('SELECT * FROM users');

    $queries = $collector->getQueries();
    expect($queries)->toHaveCount(1)
        ->and($queries->first()['sql'])->toContain('users');
});

test('it ignores regex patterns', function () {
    $collector = new QueryCollector([], ['/INFORMATION_SCHEMA/i', '/^SELECT \* FROM cache/']);

    $reflection = new \ReflectionClass($collector);
    $method = $reflection->getMethod('shouldIgnore');
    $method->setAccessible(true);

    expect($method->invoke($collector, 'SELECT * FROM INFORMATION_SCHEMA.TABLES'))->toBeTrue()
        ->and($method->invoke($collector, 'SELECT * FROM cache WHERE key = ?'))->toBeTrue()
        ->and($method->invoke($collector, 'SELECT * FROM users'))->toBeFalse();
});

test('it stores query metadata correctly', function () {
    $collector = new QueryCollector();
    $collector->start();

    DB::select('SELECT * FROM users WHERE id = ?', [123]);

    $query = $collector->getQueries()->first();
    expect($query)->toHaveKeys(['sql', 'bindings', 'time', 'connection'])
        ->and($query['sql'])->toBeString()
        ->and($query['bindings'])->toBeArray()
        ->and($query['time'])->toBeFloat()
        ->and($query['connection'])->toBe('sqlite');
});

test('it resets collection', function () {
    $collector = new QueryCollector();
    $collector->start();

    DB::select('SELECT * FROM users');
    expect($collector->getQueries())->toHaveCount(1);

    $collector->reset();
    expect($collector->getQueries())->toHaveCount(0);
});
