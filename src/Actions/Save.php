<?php

namespace Larawatcher\Actions;

use Illuminate\Support\Collection;
use Larawatcher\Entities\HydratedModel;
use Larawatcher\Entities\Query;
use Larawatcher\Larawatcher;

class Save extends BaseAction
{
    private const CHUNK_SIZE = 500;

    public function handle()
    {
        $laraWatcher = resolve(Larawatcher::class);

        if (! $laraWatcher->isEnabled()) {
            return;
        }

        $this->client->put(sprintf('processes/%s', $laraWatcher->getProcessId()), [
            'time' => (microtime(true) - $laraWatcher->getStart()) * 1000,
            'endedAt' => now()->toDateTimeString(),
            'hasQueries' => count($laraWatcher->getQueries()) > 0,
        ]);

        $explain = config('larawatcher.explain');

        collect($laraWatcher->getQueries())
            ->chunk(self::CHUNK_SIZE)
            ->each(function (Collection $queries) use ($explain, $laraWatcher) {
                $postData = $queries->map(fn (Query $query) => $query->withExplain($explain)->toArray());
                $this->client->post(
                    sprintf('processes/%s/queries/', $laraWatcher->getProcessId()),
                    $postData->toArray(),
                );
            });

        collect($laraWatcher->getHydratedModels())
            ->chunk(self::CHUNK_SIZE)
            ->each(function (Collection $models) use ($laraWatcher) {
                $postData = $models->map(fn (HydratedModel $model) => $model->toArray());
                $this->client->post(
                    sprintf('processes/%s/models/', $laraWatcher->getProcessId()),
                    $postData->toArray(),
                );
            });
    }
}
