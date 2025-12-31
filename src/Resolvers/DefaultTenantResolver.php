<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Resolvers;

use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\Contracts\TenantResolverInterface;
use NotMings\Tenancy\TenantContext;
use Psr\Container\ContainerInterface;

class DefaultTenantResolver implements TenantResolverInterface
{
    public function __construct(private ContainerInterface $container) {}

    public function resolve(): void
    {
        $httpRequest = $this->container->get('request');
        $tenantId = $httpRequest->getHeaderLine('X-Tenant-ID');
        if ($tenantId) {
            $config = $this->container->get(ConfigInterface::class);
            TenantContext::setId($tenantId);
            TenantContext::setDatabaseConfig(
                $config->get('tenancy.database.tenant_' . $tenantId, [])
            );
            TenantContext::setRedisConfig(
                $config->get('tenancy.redis.tenant_' . $tenantId, [])
            );
        }
    }
}
