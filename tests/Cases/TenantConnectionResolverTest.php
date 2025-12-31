<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Pool\PoolFactory;
use Mockery;
use NotMings\Tenancy\Database\TenantConnectionResolver;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \NotMings\Tenancy\Database\TenantConnectionResolver::connection
 * @internal
 */
class TenantConnectionResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        TenantContext::clear();
    }

    public function testRegistersTenantDatabaseConfigAndAvoidsError(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $cfg */
        $cfg = $container->get(ConfigInterface::class);

        $resolver = new TenantConnectionResolver($container);

        $tenantId = '42';
        $dbConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ];
        TenantContext::setId($tenantId);
        TenantContext::setDatabaseConfig($dbConfig);

        // Call connection; FakeDbPool in bootstrap will prevent real DB usage
        $conn = $resolver->connection();
        $this->assertNotNull($conn);

        $fingerprint = substr(md5(json_encode($dbConfig)), 0, 12);
        $tenantName = sprintf('tenant_%s_%s', $tenantId, $fingerprint);
        $this->assertTrue($cfg->has('databases.' . $tenantName));

        // Ensure PoolFactory still resolvable
        $this->assertInstanceOf(PoolFactory::class, $container->get(PoolFactory::class));
    }
}
