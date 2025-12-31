<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use NotMings\Tenancy\Exception\TenantQueueContextMissingException;
use NotMings\Tenancy\Queue\TenantRedisDriver;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @internal
 * @coversNothing
 */
class TenantRedisDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clear tenant context after each test
        TenantContext::clear();
        parent::tearDown();
    }

    public function testPushImmediateWithPrefixAndTenant(): void
    {
        $this->setPrefixEnabled(true);
        TenantContext::setId('t1');

        $driver = $this->makeDriver('queue');

        // Clean keys before test
        $redis = $this->redis();
        $waitingKey = 'tenancy:t1:queue:waiting';
        $redis->del($waitingKey);

        $job = $this->makeJob();
        $ok = $driver->push($job, 0);
        $this->assertTrue($ok);

        // Assert enqueued into tenant-prefixed waiting list
        $this->assertSame(1, $redis->lLen($waitingKey));

        // Cleanup
        $redis->del($waitingKey);
    }

    public function testPushDelayedWithPrefixAndTenant(): void
    {
        $this->setPrefixEnabled(true);
        TenantContext::setId('tenant-xyz');

        $driver = $this->makeDriver('queue');

        $redis = $this->redis();
        $delayedKey = 'tenancy:tenant-xyz:queue:delayed';
        $redis->del($delayedKey);

        $job = $this->makeJob();
        $ok = $driver->push($job, 2);
        $this->assertTrue($ok);

        $this->assertSame(1, $redis->zCard($delayedKey));

        $redis->del($delayedKey);
    }

    public function testPushImmediateWithoutPrefixNoTenant(): void
    {
        $this->setPrefixEnabled(false);
        TenantContext::clear();

        $driver = $this->makeDriver('queue');

        $redis = $this->redis();
        $waitingKey = 'queue:waiting';
        $redis->del($waitingKey);

        $job = $this->makeJob();
        $ok = $driver->push($job, 0);
        $this->assertTrue($ok);

        $this->assertSame(1, $redis->lLen($waitingKey));

        $redis->del($waitingKey);
    }

    public function testInfoThrowsWhenPrefixEnabledAndNoTenant(): void
    {
        $this->setPrefixEnabled(true);
        TenantContext::clear();

        $driver = $this->makeDriver('queue');

        $this->expectException(TenantQueueContextMissingException::class);
        $this->expectExceptionMessage('Tenancy queue prefix is enabled');
        $driver->info();
    }

    private function makeDriver(string $channel = 'queue'): TenantRedisDriver
    {
        $container = ApplicationContext::getContainer();
        $config = [
            'channel' => $channel,
            'redis' => ['pool' => 'default'],
            'timeout' => 2,
            'retry_seconds' => 10,
            'handle_timeout' => 2,
        ];
        return new TenantRedisDriver($container, $config);
    }

    private function redis(): RedisProxy
    {
        $container = ApplicationContext::getContainer();
        /** @var RedisFactory $rf */
        $rf = $container->get(RedisFactory::class);
        return $rf->get('default');
    }

    private function setPrefixEnabled(bool $enabled, string $format = 'tenancy:%s:%s'): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $cfg */
        $cfg = $container->get(ConfigInterface::class);
        $tenancyCfg = $cfg->get('tenancy', []);
        $tenancyCfg['queue']['prefix']['enabled'] = $enabled;
        $tenancyCfg['queue']['prefix']['format'] = $format;
        $cfg->set('tenancy', $tenancyCfg);
    }

    private function makeJob(): JobInterface
    {
        return new SimpleJob();
    }
}

class SimpleJob implements JobInterface
{
    private int $maxAttempts = 1;

    public function handle(): void {}

    public function fail(Throwable $e): void {}

    public function setMaxAttempts(int $maxAttempts): static
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getPoolName(): string
    {
        return 'default';
    }
}
