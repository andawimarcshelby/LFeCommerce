<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    */

    'server' => env('OCTANE_SERVER', 'swoole'),

    /*
    |--------------------------------------------------------------------------
    | Swoole Configuration
    |--------------------------------------------------------------------------
    */

    'swoole' => [
        'options' => [
            'log_file' => storage_path('logs/swoole.log'),
            'log_level' => env('SWOOLE_LOG_LEVEL', SWOOLE_LOG_INFO),
            'worker_num' => env('SWOOLE_WORKER_NUM', swoole_cpu_num() * 2),
            'task_worker_num' => env('SWOOLE_TASK_WORKER_NUM', swoole_cpu_num() * 2),
            'enable_static_handler' => true,
            'document_root' => public_path(),
            'package_max_length' => 10 * 1024 * 1024, // 10MB
            'buffer_output_size' => 10 * 1024 * 1024,
            'socket_buffer_size' => 128 * 1024 * 1024,
            'max_request' => 1000,
            'reload_async' => true,
            'max_wait_time' => 60,
            'enable_reuse_port' => true,
            'enable_coroutine' => true,
            'send_yield' => true,
            'dispatch_mode' => 2,
            'open_tcp_nodelay' => true,
            'max_coroutine' => 100000,
        ],

        'tables' => [
            'example:1000' => [
                'name' => ['type' => \Swoole\Table::TYPE_STRING, 'size' => 1000],
                'votes' => ['type' => \Swoole\Table::TYPE_INT],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RoadRunner Configuration
    |--------------------------------------------------------------------------
    */

    'roadrunner' => [
        'rpc' => [
            'host' => env('ROADRUNNER_RPC_HOST', '127.0.0.1'),
            'port' => env('ROADRUNNER_RPC_PORT', 6001),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm / Flush Bindings
    |--------------------------------------------------------------------------
    */

    'warm' => [
        // ...
    ],

    'flush' => [
        // ...
    ],

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'driver' => env('OCTANE_CACHE_DRIVER', 'array'),
        'rows' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Concurrent Tasks
    |--------------------------------------------------------------------------
    */

    'max_concurrent_tasks' => env('OCTANE_MAX_CONCURRENT_TASKS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection Threshold
    |--------------------------------------------------------------------------
    */

    'garbage_collection' => [
        'task_interval' => 1000,
        'tick_interval' => 1000,
    ],

];
