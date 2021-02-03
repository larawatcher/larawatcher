<?php

namespace Larawatcher;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Str;
use Larawatcher\Actions\InitiateProcess;
use Larawatcher\Actions\Live;
use Larawatcher\Actions\Save;
use Larawatcher\Entities\Query;
use Spatie\Backtrace\Backtrace;

final class Larawatcher
{
    private Application $app;
    private array $queries = [];
    private ?string $type = null;
    private string $attribute = 'artisan';
    private float $start;
    private ?string $tag = null;
    private bool $enabled;
    private string $processId;
    private PendingRequest $client;
    private int $hydratedModels = 0;
    private Save $saveAction;
    private InitiateProcess $initiateProcessAction;

    public function __construct(
        Application $app,
        Save $saveAction,
        InitiateProcess $initiateProcessAction,
        Live $liveAction
    ) {
        $this->app = $app;
        $this->start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $this->processId = Str::uuid()->__toString();
        $this->enabled = config('larawatcher.enabled') && $liveAction->handle();
        $this->saveAction = $saveAction;
        $this->initiateProcessAction = $initiateProcessAction;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    public function getHydratedModels(): int
    {
        return $this->hydratedModels;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function handle(): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->setProcessType()
            ->listenForQueryEvents()
            ->listenForModelHydration()
            ->listenForExceptions()
            ->prepareForSave();
    }

    public function tag($tag): void
    {
        $this->tag = $tag;
    }

    public function untag($tag): void
    {
        if ($this->tag !== $tag) {
            throw new \Exception(sprintf('Can not untag "%s". Either it is not defined or using a wrong name', $tag));
        }

        $this->tag = null;
    }

    private function setProcessType(): self
    {
        $this->app['events']->listen(RouteMatched::class, function (RouteMatched $event) {
            if (! is_null($this->type)) {
                return;
            }

            $attribute = $event->route->uri;

            foreach ($event->route->parameters() as $key => $parameter) {
                $attribute = Str::replaceFirst(sprintf('{%s}', $key), $parameter, $attribute);
            }

            $this->type = 'route';
            $this->attribute = $attribute;
            $this->initiateProcessAction->handle();
        });

        $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event) {
            if (! is_null($this->type) || Str::startsWith($event->command, 'queue:')) {
                return;
            }

            $this->type = 'command';
            $this->attribute = $event->command ?? 'artisan';
            $this->initiateProcessAction->handle();
        });

        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            if (! is_null($this->type)) {
                return;
            }

            $this->type = 'job';
            $this->attribute = $event->job->resolveName();
            $this->initiateProcessAction->handle();
        });

        return $this;
    }

    private function listenForQueryEvents(): self
    {
        $this->app['events']->listen(QueryExecuted::class, function ($event) {
            if (config('larawatcher.tags_only') && is_null($this->tag)) {
                return;
            }

            $this->queries[] = new Query($event, Backtrace::create(), $this->tag);
        });

        return $this;
    }

    private function listenForModelHydration(): self
    {
        $this->app['events']->listen('eloquent.retrieved:*', function ($_, $models) {
            $this->hydratedModels = $this->hydratedModels + count($models);
        });

        return $this;
    }

    private function listenForExceptions(): self
    {
        $this->app['events']->listen(MessageLogged::class, function ($event) {
            if (! array_key_exists('exception', $event->context)) {
                return;
            }

            $this->saveAction->handle();
        });

        return $this;
    }

    private function prepareForSave(): self
    {
        collect([
            'job' => JobProcessed::class,
            'command' => CommandFinished::class,
        ])->each(function ($event, $type) {
            $this->app['events']->listen($event, function () use ($type) {
                if ($this->type !== $type) {
                    return;
                }

                $this->saveAction->handle();
            });
        });

        return $this;
    }
}
