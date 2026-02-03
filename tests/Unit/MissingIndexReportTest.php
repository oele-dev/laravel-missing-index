<?php

use OeleDev\MissingIndex\Reports\MissingIndexReport;

test('it creates report with all properties', function () {
    $report = new MissingIndexReport(
        sql: 'SELECT * FROM users WHERE email = ?',
        table: 'users',
        columns: ['email'],
        type: 'ALL',
        key: null,
        rows: 10000,
        extra: 'Using filesort',
        suggestion: 'ALTER TABLE `users` ADD INDEX `idx_users_email` (email)',
        executionTime: 150.5
    );

    expect($report->sql)->toBe('SELECT * FROM users WHERE email = ?')
        ->and($report->table)->toBe('users')
        ->and($report->columns)->toBe(['email'])
        ->and($report->type)->toBe('ALL')
        ->and($report->key)->toBeNull()
        ->and($report->rows)->toBe(10000)
        ->and($report->extra)->toBe('Using filesort')
        ->and($report->suggestion)->toBe('ALTER TABLE `users` ADD INDEX `idx_users_email` (email)')
        ->and($report->executionTime)->toBe(150.5);
});

test('it converts to array correctly', function () {
    $report = new MissingIndexReport(
        sql: 'SELECT * FROM users WHERE email = ?',
        table: 'users',
        columns: ['email', 'name'],
        type: 'ALL',
        key: null,
        rows: 5000,
        extra: 'Using temporary',
        suggestion: 'ALTER TABLE `users` ADD INDEX `idx_users_email_name` (email, name)',
        executionTime: 200.75
    );

    $array = $report->toArray();

    expect($array)->toBeArray()
        ->and($array['sql'])->toBe('SELECT * FROM users WHERE email = ?')
        ->and($array['table'])->toBe('users')
        ->and($array['columns'])->toBe(['email', 'name'])
        ->and($array['type'])->toBe('ALL')
        ->and($array['key'])->toBeNull()
        ->and($array['rows'])->toBe(5000)
        ->and($array['extra'])->toBe('Using temporary')
        ->and($array['suggestion'])->toBe('ALTER TABLE `users` ADD INDEX `idx_users_email_name` (email, name)')
        ->and($array['execution_time'])->toBe(200.75);
});
