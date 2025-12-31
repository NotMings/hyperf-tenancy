<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TenantContextTest extends TestCase
{
    public function testSetGetAndClear(): void
    {
        TenantContext::clear();
        $this->assertNull(TenantContext::getId());
        $this->assertSame([], TenantContext::getDatabaseConfig());
        $this->assertSame([], TenantContext::getRedisConfig());

        TenantContext::setId('t-1');
        TenantContext::setDatabaseConfig(['driver' => 'sqlite', 'database' => ':memory:']);
        TenantContext::setRedisConfig(['host' => '127.0.0.1', 'db' => 2]);

        $this->assertSame('t-1', TenantContext::getId());
        $this->assertSame(['driver' => 'sqlite', 'database' => ':memory:'], TenantContext::getDatabaseConfig());
        $this->assertSame(['host' => '127.0.0.1', 'db' => 2], TenantContext::getRedisConfig());

        TenantContext::clear();
        $this->assertNull(TenantContext::getId());
        $this->assertSame([], TenantContext::getDatabaseConfig());
        $this->assertSame([], TenantContext::getRedisConfig());
    }
}
