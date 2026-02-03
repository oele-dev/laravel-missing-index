<?php

namespace OeleDev\MissingIndex\Reports;

class MissingIndexReport
{
    public function __construct(
        public readonly string $sql,
        public readonly string $table,
        public readonly array $columns,
        public readonly string $type,
        public readonly ?string $key,
        public readonly int $rows,
        public readonly string $extra,
        public readonly string $suggestion,
        public readonly float $executionTime
    ) {
    }

    public function toArray(): array
    {
        return [
            'sql' => $this->sql,
            'table' => $this->table,
            'columns' => $this->columns,
            'type' => $this->type,
            'key' => $this->key,
            'rows' => $this->rows,
            'extra' => $this->extra,
            'suggestion' => $this->suggestion,
            'execution_time' => $this->executionTime,
        ];
    }
}
