<?php

declare(strict_types=1);

namespace NotMings\Tenancy;

use Hyperf\AsyncQueue\Driver\RedisDriver as HyperfRedisDriver;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolverInterface as DatabaseConnectionResolverInterface;
use Hyperf\Redis\RedisFactory;
use NotMings\Tenancy\Contracts\TenantResolverInterface;
use NotMings\Tenancy\Database\TenantConnectionResolver;
use NotMings\Tenancy\Database\TenantRedisFactory;
use NotMings\Tenancy\Middleware\TenantMiddleware;
use NotMings\Tenancy\Queue\TenantRedisDriver;
use NotMings\Tenancy\Resolvers\DefaultTenantResolver;
use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                TenantResolverInterface::class => function (ContainerInterface $container) {
                    $config = $container->get(ConfigInterface::class);
                    $resolverClass = $config->get('tenancy.resolver', DefaultTenantResolver::class);

                    return $container->get($resolverClass);
                },
                DatabaseConnectionResolverInterface::class => TenantConnectionResolver::class,
                RedisFactory::class => TenantRedisFactory::class,
                HyperfRedisDriver::class => TenantRedisDriver::class,
            ],
            'commands' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'middlewares' => [
                'http' => [
                    TenantMiddleware::class,
                ],
            ],
            'publish' => [
                [
                    'id' => 'tenancy',
                    'description' => 'Tenancy config',
                    'source' => __DIR__ . '/config/tenancy.php',
                    'destination' => BASE_PATH . '/config/autoload/tenancy.php',
                ],
            ],
        ];
    }
}
