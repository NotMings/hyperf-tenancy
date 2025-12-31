<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Database;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionInterface;
use Hyperf\DbConnection\ConnectionResolver;
use NotMings\Tenancy\TenantContext;

class TenantConnectionResolver extends ConnectionResolver
{
    public function connection(?string $name = null): ConnectionInterface
    {
        $tenantId = TenantContext::getId();
        $tenantDbConfig = TenantContext::getDatabaseConfig();

        // 若携带完整的多租库配置，则基于该配置动态注册连接池并使用租户专属连接名
        if ($tenantId !== null && $tenantDbConfig !== []) {
            // 连接名：租户ID + 配置指纹，确保不同配置严格隔离，避免上下文复用造成的污染
            $fingerprint = substr(md5(json_encode($tenantDbConfig)), 0, 12);
            $tenantConnectionName = sprintf('tenant_%s_%s', $tenantId, $fingerprint);

            /** @var ConfigInterface $config */
            $config = $this->container->get(ConfigInterface::class);
            $dbKey = sprintf('databases.%s', $tenantConnectionName);

            // 若尚未注册该连接配置，则写入到全局配置中，以便 DbPool 能够创建连接池
            if (! $config->has($dbKey)) {
                $final = $tenantDbConfig;

                // 若未显式提供 pool 配置，则尽可能沿用 default 的 pool 设置，避免无界并发造成资源风险
                $defaultKey = 'databases.default';
                if (! isset($final['pool']) && $config->has($defaultKey)) {
                    $default = (array) $config->get($defaultKey);
                    if (isset($default['pool'])) {
                        $final['pool'] = $default['pool'];
                    }
                }

                $config->set($dbKey, $final);
            }

            // 始终使用租户专属连接名进行获取，避免默认连接导致的上下文污染
            return parent::connection($tenantConnectionName);
        }

        // 无租户配置时，保持原有行为
        return parent::connection($name);
    }
}
