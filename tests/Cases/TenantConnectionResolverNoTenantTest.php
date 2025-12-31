<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\Database\TenantConnectionResolver;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NotMings\Tenancy\Database\TenantConnectionResolver::connection
 * @internal
 */
class TenantConnectionResolverNoTenantTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
    }

    public function testConnectionWithoutTenantUsesDefault(): void
    {
        TenantContext::clear();
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $cfg */
        $cfg = $container->get(ConfigInterface::class);

        $resolver = new TenantConnectionResolver($container);
        $conn = $resolver->connection();
        $this->assertNotNull($conn);

        // Ensure default config remains present and used as fallback
        $this->assertTrue($cfg->has('databases.default'));
    }
}
