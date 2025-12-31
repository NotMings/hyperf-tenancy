<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\AsyncQueue\JobInterface;
use NotMings\Tenancy\Queue\TenantJob;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * @internal
 * @coversNothing
 */
class TenantJobTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function testHandleSetsAndClearsTenantContext(): void
    {
        $inner = new class implements JobInterface {
            public ?string $observedId = null;

            public array $observedDb = [];

            public array $observedRedis = [];

            public bool $handled = false;

            private int $maxAttempts = 1;

            public function fail(Throwable $e): void {}

            public function handle(): void
            {
                $this->observedId = TenantContext::getId();
                $this->observedDb = TenantContext::getDatabaseConfig();
                $this->observedRedis = TenantContext::getRedisConfig();
                $this->handled = true;
            }

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
        };

        $tenantId = 'tenant-123';
        $db = ['driver' => 'sqlite', 'database' => ':memory:'];
        $redis = ['host' => '127.0.0.1'];

        $job = new TenantJob($inner, $tenantId, $db, $redis);
        $job->handle();

        // Inner job observed context
        $this->assertTrue($inner->handled);
        $this->assertSame($tenantId, $inner->observedId);
        $this->assertSame($db, $inner->observedDb);
        $this->assertSame($redis, $inner->observedRedis);

        // Context cleared afterwards
        $this->assertNull(TenantContext::getId());
        $this->assertSame([], TenantContext::getDatabaseConfig());
        $this->assertSame([], TenantContext::getRedisConfig());
    }

    public function testFailSetsAndClearsTenantContext(): void
    {
        $inner = new class implements JobInterface {
            public ?string $observedId = null;

            public array $observedDb = [];

            public array $observedRedis = [];

            public bool $failedCalled = false;

            private int $maxAttempts = 1;

            public function handle(): void {}

            public function fail(Throwable $e): void
            {
                $this->observedId = TenantContext::getId();
                $this->observedDb = TenantContext::getDatabaseConfig();
                $this->observedRedis = TenantContext::getRedisConfig();
                $this->failedCalled = true;
            }

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
        };

        $tenantId = 'tenant-FAIL';
        $db = ['driver' => 'sqlite', 'database' => ':memory:'];
        $redis = ['host' => '127.0.0.1'];

        $job = new TenantJob($inner, $tenantId, $db, $redis);
        $job->fail(new RuntimeException('boom'));

        // Inner job observed context in fail()
        $this->assertTrue($inner->failedCalled);
        $this->assertSame($tenantId, $inner->observedId);
        $this->assertSame($db, $inner->observedDb);
        $this->assertSame($redis, $inner->observedRedis);

        // Context cleared afterwards
        $this->assertNull(TenantContext::getId());
        $this->assertSame([], TenantContext::getDatabaseConfig());
        $this->assertSame([], TenantContext::getRedisConfig());
    }
}
