<?php

namespace Larawatcher\Actions;

class Live extends BaseAction
{
    public function handle(): bool
    {
        try {
            return $this->client->get('/live')->successful();
        } catch (\Exception $exception) {
            return false;
        }
    }
}
