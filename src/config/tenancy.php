<?php

declare(strict_types=1);

use NotMings\Tenancy\Resolvers\DefaultTenantResolver;

use function Hyperf\Support\env;

return [
    'resolver' => DefaultTenantResolver::class,

    'database' => [
        'tenant_0' => [
            'driver' => env('TENANT_DB_DRIVER', 'mysql'),
            'host' => env('TENANT_DB_HOST', 'localhost'),
            'database' => 'tenant_0',
            'port' => (int) env('TENANT_DB_PORT', 3306),
            'username' => env('TENANT_DB_USERNAME', ''),
            'password' => env('TENANT_DB_PASSWORD', ''),
            'charset' => env('TENANT_DB_CHARSET', 'utf8mb4'),
            'collation' => env('TENANT_DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('TENANT_DB_PREFIX', ''),
        ],
    ],

    'redis' => [
        'tenant_0' => [
            'host' => env('TENANT_REDIS_HOST', 'localhost'),
            'port' => (int) env('TENANT_REDIS_PORT', 6379),
            'auth' => env('TENANT_REDIS_AUTH', null),
            'db' => (int) env('TENANT_REDIS_DB', 0),
        ],
    ],

    // 队列键前缀配置：默认关闭以实现“完全无感”，如需按租户拆分频道可开启
    'queue' => [
        'prefix' => [
            'enabled' => (bool) env('TENANCY_QUEUE_PREFIX_ENABLED', false),
            'format' => env('TENANCY_QUEUE_PREFIX_FORMAT', 'tenancy:%s:%s'),
        ],
    ],
];
