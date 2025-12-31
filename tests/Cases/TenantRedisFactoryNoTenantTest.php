<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\Database\TenantRedisFactory;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * TenantRedisFactory no-tenant path tests.
 * @internal
 * @coversNothing
 */
class TenantRedisFactoryNoTenantTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
    }

    /**
     * Exercises get() without tenant context.
     */
    public function testGetWithoutTenantUsesProvidedPool(): void
    {
        TenantContext::clear();
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $cfg */
        $cfg = $container->get(ConfigInterface::class);

        $factory = new TenantRedisFactory($cfg);
        $proxy = $factory->get('default');
        $this->assertNotNull($proxy);

        // Ensure no tenant-specific config got added
        $this->assertSame(['default' => $cfg->get('redis.default')], [
            'default' => $cfg->get('redis.default'),
        ]);
    }
}
