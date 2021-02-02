<?php

namespace Larawatcher\Actions;

use Larawatcher\Larawatcher;

class InitiateProcess extends BaseAction
{
    public function handle()
    {
        $laraWatcher = resolve(Larawatcher::class);

        $path = config('larawatcher.app_path') ?: base_path();

        $response = $this->client
            ->post('applications', [
                'name' => config('app.name'),
                'hash' => md5($path),
                'path' => $path,
            ])
            ->json();

        $this->client->post('processes', [
            'applicationId' => data_get($response, 'data.id'),
            'uuid' => $laraWatcher->getProcessId(),
            'type' => $laraWatcher->getType() ?? 'route',
            'attribute' => $laraWatcher->getAttribute(),
            'startedAt' => now()->toDateTimeString(),
        ]);
    }
}
