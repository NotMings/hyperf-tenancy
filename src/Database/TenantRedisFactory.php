<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Database;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use NotMings\Tenancy\TenantContext;

use function Hyperf\Support\make;

class TenantRedisFactory extends RedisFactory
{
    public function get(string $poolName): RedisProxy
    {
        $tenantId = TenantContext::getId();
        $tenantRedisConfig = TenantContext::getRedisConfig();

        if ($tenantId !== null && $tenantRedisConfig !== []) {
            // 连接名：租户ID + 配置指纹，确保不同配置严格隔离
            $fingerprint = substr(md5(json_encode($tenantRedisConfig)), 0, 12);
            $tenantPoolName = sprintf('tenant_%s_%s', $tenantId, $fingerprint);

            $container = ApplicationContext::getContainer();
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $redisKey = sprintf('redis.%s', $tenantPoolName);

            // 若尚未注册该连接配置，则写入到全局配置中，以便 RedisPool 能够创建连接池
            if (! $config->has($redisKey)) {
                $final = $tenantRedisConfig;

                // 若未显式提供 pool 配置，则尽可能沿用 default 的 pool 设置
                $defaultKey = 'redis.default';
                if (! isset($final['pool']) && $config->has($defaultKey)) {
                    $default = (array) $config->get($defaultKey);
                    if (isset($default['pool'])) {
                        $final['pool'] = $default['pool'];
                    }
                }

                $config->set($redisKey, $final);
            }

            // 确保工厂已缓存该 Proxy；若未创建则按需创建
            if (! isset($this->proxies[$tenantPoolName])) {
                $this->proxies[$tenantPoolName] = make(RedisProxy::class, ['pool' => $tenantPoolName]);
            }

            return parent::get($tenantPoolName);
        }

        // 无租户配置时，保持原有行为
        return parent::get($poolName);
    }
}
