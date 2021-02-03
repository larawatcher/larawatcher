<?php

namespace Larawatcher\Entities;

use Spatie\Backtrace\Backtrace;

final class HydratedModel extends BaseEntity
{
    private string $model;

    public function __construct(string $model, Backtrace $backtrace)
    {
        $this->model = $model;
        $this->frames = $this->frames($backtrace);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function toArray(): array
    {
        $firstFrame = $this->frames->first();

        return [
            'groupHash' => md5($this->model),
            'class' => $this->model,
            'file' => $this->getFile($firstFrame),
            'line' => $firstFrame->lineNumber,
        ];
    }
}
