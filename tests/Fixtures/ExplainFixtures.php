<?php

namespace OeleDev\MissingIndex\Tests\Fixtures;

class ExplainFixtures
{
    public static function fullTableScan(): array
    {
        return [
            (object) [
                'id' => 1,
                'select_type' => 'SIMPLE',
                'table' => 'users',
                'type' => 'ALL',
                'possible_keys' => null,
                'key' => null,
                'key_len' => null,
                'ref' => null,
                'rows' => 10000,
                'Extra' => '',
            ],
        ];
    }

    public static function indexedQuery(): array
    {
        return [
            (object) [
                'id' => 1,
                'select_type' => 'SIMPLE',
                'table' => 'users',
                'type' => 'ref',
                'possible_keys' => 'idx_email',
                'key' => 'idx_email',
                'key_len' => '767',
                'ref' => 'const',
                'rows' => 1,
                'Extra' => 'Using index condition',
            ],
        ];
    }

    public static function filesortQuery(): array
    {
        return [
            (object) [
                'id' => 1,
                'select_type' => 'SIMPLE',
                'table' => 'posts',
                'type' => 'ALL',
                'possible_keys' => null,
                'key' => null,
                'key_len' => null,
                'ref' => null,
                'rows' => 5000,
                'Extra' => 'Using filesort',
            ],
        ];
    }

    public static function temporaryTableQuery(): array
    {
        return [
            (object) [
                'id' => 1,
                'select_type' => 'SIMPLE',
                'table' => 'orders',
                'type' => 'ALL',
                'possible_keys' => null,
                'key' => null,
                'key_len' => null,
                'ref' => null,
                'rows' => 3000,
                'Extra' => 'Using temporary; Using filesort',
            ],
        ];
    }

    public static function noIndexUsed(): array
    {
        return [
            (object) [
                'id' => 1,
                'select_type' => 'SIMPLE',
                'table' => 'products',
                'type' => 'ref',
                'possible_keys' => 'idx_category',
                'key' => null,
                'key_len' => null,
                'ref' => null,
                'rows' => 100,
                'Extra' => '',
            ],
        ];
    }
}
