<?php

use OeleDev\MissingIndex\Analyzers\ExplainAnalyzer;

beforeEach(function () {
    $this->analyzer = new ExplainAnalyzer();
});

test('it identifies full table scan as warning condition', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('shouldWarn');
    $method->setAccessible(true);

    $explainData = [
        'table' => 'users',
        'type' => 'ALL',
        'key' => null,
        'rows' => 10000,
        'extra' => '',
    ];

    expect($method->invoke($this->analyzer, $explainData))->toBeTrue();
});

test('it identifies missing index as warning condition', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('shouldWarn');
    $method->setAccessible(true);

    $explainData = [
        'table' => 'products',
        'type' => 'ref',
        'key' => null,
        'rows' => 100,
        'extra' => '',
    ];

    expect($method->invoke($this->analyzer, $explainData))->toBeTrue();
});

test('it identifies filesort usage as warning condition', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('shouldWarn');
    $method->setAccessible(true);

    $explainData = [
        'table' => 'posts',
        'type' => 'ALL',
        'key' => null,
        'rows' => 5000,
        'extra' => 'Using filesort',
    ];

    expect($method->invoke($this->analyzer, $explainData))->toBeTrue();
});

test('it identifies temporary table usage as warning condition', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('shouldWarn');
    $method->setAccessible(true);

    $explainData = [
        'table' => 'orders',
        'type' => 'ALL',
        'key' => null,
        'rows' => 3000,
        'extra' => 'Using temporary; Using filesort',
    ];

    expect($method->invoke($this->analyzer, $explainData))->toBeTrue();
});

test('it does not warn for properly indexed queries', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('shouldWarn');
    $method->setAccessible(true);

    $explainData = [
        'table' => 'users',
        'type' => 'ref',
        'key' => 'idx_email',
        'rows' => 1,
        'extra' => 'Using index condition',
    ];

    expect($method->invoke($this->analyzer, $explainData))->toBeFalse();
});

test('it extracts WHERE columns from SQL', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('extractColumns');
    $method->setAccessible(true);

    $sql = 'SELECT * FROM users WHERE email = ?';
    $columns = $method->invoke($this->analyzer, $sql);

    expect($columns)->toContain('email');
});

test('it extracts JOIN columns from SQL', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('extractColumns');
    $method->setAccessible(true);

    $sql = 'SELECT * FROM users JOIN posts ON users.id = posts.user_id';
    $columns = $method->invoke($this->analyzer, $sql);

    // The regex captures the column from the JOIN condition
    expect($columns)->toContain('id');
});

test('it extracts ORDER BY columns from SQL', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('extractColumns');
    $method->setAccessible(true);

    $sql = 'SELECT * FROM posts ORDER BY created_at DESC';
    $columns = $method->invoke($this->analyzer, $sql);

    expect($columns)->toContain('created_at');
});

test('it generates index suggestion with columns', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('generateSuggestion');
    $method->setAccessible(true);

    $suggestion = $method->invoke($this->analyzer, 'users', ['email', 'status']);

    expect($suggestion)->toContain('ALTER TABLE')
        ->and($suggestion)->toContain('users')
        ->and($suggestion)->toContain('ADD INDEX')
        ->and($suggestion)->toContain('email')
        ->and($suggestion)->toContain('status');
});

test('it generates index suggestion without specific columns', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('generateSuggestion');
    $method->setAccessible(true);

    $suggestion = $method->invoke($this->analyzer, 'users', []);

    expect($suggestion)->toContain('users')
        ->and($suggestion)->toContain('index');
});

test('it parses EXPLAIN output correctly', function () {
    $reflection = new \ReflectionClass($this->analyzer);
    $method = $reflection->getMethod('parseExplainOutput');
    $method->setAccessible(true);

    $explainRow = [
        'table' => 'users',
        'type' => 'ALL',
        'key' => null,
        'rows' => 10000,
        'Extra' => 'Using filesort',
    ];

    $parsed = $method->invoke($this->analyzer, $explainRow);

    expect($parsed['table'])->toBe('users')
        ->and($parsed['type'])->toBe('ALL')
        ->and($parsed['key'])->toBeNull()
        ->and($parsed['rows'])->toBe(10000)
        ->and($parsed['extra'])->toBe('Using filesort');
});
