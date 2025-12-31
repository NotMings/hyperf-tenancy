<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Mockery;
use NotMings\Tenancy\Resolvers\DefaultTenantResolver;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class DefaultTenantResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        TenantContext::clear();
    }

    public function testResolveWithHeaderSetsContext(): void
    {
        $tenantId = '1';
        $cfg = new Config([
            'tenancy' => [
                'database' => [
                    'tenant_1' => ['driver' => 'sqlite', 'database' => ':memory:'],
                ],
                'redis' => [
                    'tenant_1' => ['host' => '127.0.0.1', 'db' => 1],
                ],
            ],
        ]);

        $request = new class {
            public function getHeaderLine(string $name): string
            {
                return $name === 'X-Tenant-ID' ? '1' : '';
            }
        };

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with('request')->andReturn($request);
        $container->shouldReceive('get')->with(ConfigInterface::class)->andReturn($cfg);

        $resolver = new DefaultTenantResolver($container);
        $resolver->resolve();

        $this->assertSame($tenantId, TenantContext::getId());
        $this->assertSame(['driver' => 'sqlite', 'database' => ':memory:'], TenantContext::getDatabaseConfig());
        $this->assertSame(['host' => '127.0.0.1', 'db' => 1], TenantContext::getRedisConfig());
    }

    public function testResolveWithoutHeaderDoesNothing(): void
    {
        $request = new class {
            public function getHeaderLine(string $name): string
            {
                return '';
            }
        };

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with('request')->andReturn($request);
        // Config should not be fetched
        $container->shouldNotReceive('get')->with(ConfigInterface::class);

        $resolver = new DefaultTenantResolver($container);
        $resolver->resolve();

        $this->assertNull(TenantContext::getId());
        $this->assertSame([], TenantContext::getDatabaseConfig());
        $this->assertSame([], TenantContext::getRedisConfig());
    }
}
