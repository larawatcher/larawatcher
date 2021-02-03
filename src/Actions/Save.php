<?php

namespace Larawatcher\Actions;

use Illuminate\Support\Collection;
use Larawatcher\Entities\Query;
use Larawatcher\Larawatcher;

class Save extends BaseAction
{
    private const CHUNK_SIZE = 50;

    public function handle()
    {
        $laraWatcher = resolve(Larawatcher::class);

        if (! $laraWatcher->isEnabled()) {
            return;
        }

        $endedAt = now()->toDateTimeString();
        $time = (microtime(true) - $laraWatcher->getStart()) * 1000;

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

        $this->client->put(sprintf('processes/%s', $laraWatcher->getProcessId()), [
            'time' => $time,
            'endedAt' => $endedAt,
            'models' => $laraWatcher->getHydratedModels(),
        ]);
    }
}
