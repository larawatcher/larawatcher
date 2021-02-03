<?php

namespace Larawatcher\Entities;

use Exception;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Spatie\Backtrace\Backtrace;

final class Query extends BaseEntity
{
    private QueryExecuted $queryExecuted;
    private ?string $tag;
    private bool $withExplain = false;

    public function __construct(QueryExecuted $queryExecuted, Backtrace $backtrace, string $tag = null)
    {
        $this->queryExecuted = $queryExecuted;
        $this->tag = $tag;
        $this->frames = $this->frames($backtrace);
    }

    public function getQueryExecuted(): QueryExecuted
    {
        return $this->queryExecuted;
    }

    public function withExplain($explain = true): Query
    {
        $this->withExplain = $explain;

        return $this;
    }

    public function toArray(): array
    {
        $firstFrame = $this->frames->first();

        return [
            'sql' => $this->queryExecuted->sql,
            'groupHash' => md5($this->queryExecuted->sql),
            'bindings' => json_encode($this->getBindings()),
            'time' => $this->queryExecuted->time,
            'connection' => $this->queryExecuted->connection->getName(),
            'file' => $this->getFile($firstFrame),
            'line' => $firstFrame->lineNumber,
            'tag' => $this->tag,
            'explain' => $this->withExplain ? json_encode($this->explain()) : null,
            'backtrace' => $this->getBacktrace()->toJson(),
        ];
    }

    private function mergeWithBindings(): string
    {
        return vsprintf(str_replace('?', '"%s"', $this->queryExecuted->sql), $this->queryExecuted->bindings);
    }

    private function explain(): array
    {
        try {
            return DB::select(sprintf('explain %s', $this->mergeWithBindings()));
        } catch (Exception $e) {
            return [];
        }
    }

    private function getBindings(): array
    {
        return array_map(function ($binding) {
            try {
                return is_object($binding) ? $binding->__toString() : $binding;
            } catch (Exception $e) {
                return 'Can not resolve binding';
            }
        }, $this->queryExecuted->bindings);
    }
}
