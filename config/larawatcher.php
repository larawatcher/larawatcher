<?php

return [
    /*
     * Use this setting if you want to disable/enable Larawatcher to listen for query execution
     */
    'enabled' => env('LARAWATCHER_ENABLED', true),
    /*
     * The `endpoint` will be used to send requests to Larawatcher desktop app.
     * `localhost` is good if you are using something like Laravel valet.
     * For Docker you may use `host.docker.internal` and for Laravel Homestead you may use `10.0.2.2`.
     */
    'endpoint' => env('LARAWATCHER_ENDPOINT', 'localhost'),
    /*
     * The `port` number used together with `endpoint` to send requests to Larawatcher desktop app.
     * The port should match the port you defined in Larawatcher desktop app (default 3000)
     */
    'port' => env('LARAWATCHER_PORT', 3000),
    /*
     * If you need Larawatcher to report queries wrapped between tags only, you may set `tags_only`
     * to `true`.
     */
    'tags_only' => env('LARAWATCHER_TAGS_ONLY', false),
    /**
     * If you need Larawatcher run `EXPLAIN` against the queries, set `explain` to `true`.
     */
    'explain' => env('LARAWATCHER_EXPLAIN', false),
    /**
     * In order to use `Open in editor` feature on desktop app and better backtrace, Larawatcher needs to know
     * where on your machine the code lives. If you are using Laravel valet, you may leave this blank and it will
     * figure it out, however in case of Docker or Laravel Homestead you need to be explicit about the path.
     */
    'app_path' => null,
    /**
     * This setting will be used to define the route groups that Larawatcher needs to watch for query execution.
     */
    'middleware_groups' => env('LARAWATCHER_MIDDLEWARE_GROUPS', ['web', 'api']),
];
