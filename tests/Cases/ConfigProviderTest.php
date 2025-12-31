<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\AsyncQueue\Driver\RedisDriver as HyperfRedisDriver;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolverInterface as DatabaseConnectionResolverInterface;
use Hyperf\Redis\RedisFactory;
use NotMings\Tenancy\ConfigProvider;
use NotMings\Tenancy\Contracts\TenantResolverInterface;
use NotMings\Tenancy\Database\TenantConnectionResolver;
use NotMings\Tenancy\Database\TenantRedisFactory;
use NotMings\Tenancy\Middleware\TenantMiddleware;
use NotMings\Tenancy\Queue\TenantRedisDriver;
use NotMings\Tenancy\Resolvers\DefaultTenantResolver;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ConfigProviderTest extends TestCase
{
    public function testInvokeReturnsExpectedStructure(): void
    {
        $provider = new ConfigProvider();
        $config = $provider->__invoke();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('commands', $config);
        $this->assertArrayHasKey('annotations', $config);
        $this->assertArrayHasKey('middlewares', $config);
        $this->assertArrayHasKey('publish', $config);

        $deps = $config['dependencies'];
        $this->assertArrayHasKey(TenantResolverInterface::class, $deps);

        $publish = $config['publish'][0] ?? [];
        $this->assertSame('tenancy', $publish['id'] ?? null);
        $this->assertSame('Tenancy config', $publish['description'] ?? null);
        $this->assertStringEndsWith('/src/config/tenancy.php', $publish['source'] ?? '');
        $this->assertStringEndsWith('/config/autoload/tenancy.php', $publish['destination'] ?? '');
    }

    public function testTenantResolverDependencyReturnsDefaultResolver(): void
    {
        $container = ApplicationContext::getContainer();
        // Configure resolver explicitly to default resolver for determinism.
        /** @var ConfigInterface $cfg */
        $cfg = $container->get(ConfigInterface::class);
        $cfg->set('tenancy.resolver', DefaultTenantResolver::class);

        // Pre-bind DefaultTenantResolver for deterministic resolution.
        $container->set(DefaultTenantResolver::class, new DefaultTenantResolver($container));

        $provider = new ConfigProvider();
        $deps = $provider->__invoke()['dependencies'];

        $factory = $deps[TenantResolverInterface::class];
        $resolver = $factory($container);

        $this->assertInstanceOf(DefaultTenantResolver::class, $resolver);
    }

    public function testProviderArrayStructureAndDependencies(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('middlewares', $config);
        $this->assertArrayHasKey('publish', $config);

        $deps = $config['dependencies'];
        $this->assertArrayHasKey(TenantResolverInterface::class, $deps);
        $this->assertIsCallable($deps[TenantResolverInterface::class]);

        $this->assertSame(TenantConnectionResolver::class, $deps[DatabaseConnectionResolverInterface::class]);
        $this->assertSame(TenantRedisFactory::class, $deps[RedisFactory::class]);
        $this->assertSame(TenantRedisDriver::class, $deps[HyperfRedisDriver::class]);

        $this->assertContains(TenantMiddleware::class, $config['middlewares']['http']);
    }
}
