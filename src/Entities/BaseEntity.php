<?php

namespace Larawatcher\Entities;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;

abstract class BaseEntity
{
    protected Collection $frames;

    abstract public function toArray(): array;

    protected function frames(Backtrace $backtrace): Collection
    {
        return collect($backtrace->frames())
            ->filter(
                fn(Frame $frame) => !Str::contains($frame->file, [
                    '/vendor/',
                    'Larawatcher.php',
                    '/laravel-db-profiler/',
                    '/larawatcher/',
                ]) && $frame->lineNumber !== 0,
            )
            ->values();
    }

    protected function getFile(Frame $frame)
    {
        return Str::replaceFirst(base_path(), '', $frame->file);
    }

    protected function getBacktrace(): Collection
    {
        return $this->frames->map(
            fn(Frame $frame) => [
                'file' => $this->getFile($frame),
                'line' => $frame->lineNumber,
                'code' => $frame->getSnippet(5),
            ],
        );
    }
}
