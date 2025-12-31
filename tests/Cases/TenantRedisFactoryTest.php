<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\Database\TenantRedisFactory;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * TenantRedisFactory tests.
 * @internal
 * @coversNothing
 */
class TenantRedisFactoryTest extends TestCase
{
    /**
     * Exercises get() in tenant context.
     */
    public function testGetUsesTenantPoolWhenContextPresent(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $cfg */
        $cfg = $container->get(ConfigInterface::class);

        // Ensure default redis exists
        $this->assertTrue($cfg->has('redis.default'));

        $factory = new TenantRedisFactory($cfg);

        $tenantId = '1';
        $tenantRedisConfig = ['host' => '127.0.0.1', 'port' => 6380, 'db' => 5];
        TenantContext::setId($tenantId);
        TenantContext::setRedisConfig($tenantRedisConfig);

        // Should not throw; and config should be registered under computed pool name
        $factory->get('default');

        $fingerprint = substr(md5(json_encode($tenantRedisConfig)), 0, 12);
        $tenantPoolName = sprintf('tenant_%s_%s', $tenantId, $fingerprint);

        $this->assertTrue($cfg->has('redis.' . $tenantPoolName));
        // And tenant pool can be retrieved
        $this->assertNotNull($factory->get($tenantPoolName));
    }
}
