<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Queue;

use Hyperf\AsyncQueue\Job;
use Hyperf\AsyncQueue\JobInterface;
use NotMings\Tenancy\TenantContext;
use Throwable;

class TenantJob extends Job implements JobInterface
{
    public function __construct(
        private readonly JobInterface $inner,
        private readonly ?string $tenantId,
        private readonly array $tenantDatabaseConfig,
        private readonly array $tenantRedisConfig,
    ) {}

    public function handle(): void
    {
        // 在消费侧设置租户上下文，确保 Dynamic* 连接管理器与租户化逻辑生效
        TenantContext::setId($this->tenantId);
        if ($this->tenantDatabaseConfig) {
            TenantContext::setDatabaseConfig($this->tenantDatabaseConfig);
        }
        if ($this->tenantRedisConfig) {
            TenantContext::setRedisConfig($this->tenantRedisConfig);
        }

        try {
            $this->inner->handle();
        } finally {
            TenantContext::clear();
        }
    }

    public function fail(Throwable $e): void
    {
        TenantContext::setId($this->tenantId);
        if ($this->tenantDatabaseConfig) {
            TenantContext::setDatabaseConfig($this->tenantDatabaseConfig);
        }
        if ($this->tenantRedisConfig) {
            TenantContext::setRedisConfig($this->tenantRedisConfig);
        }

        try {
            $this->inner->fail($e);
        } finally {
            TenantContext::clear();
        }
    }

    public function setMaxAttempts(int $maxAttempts): static
    {
        $this->inner->setMaxAttempts($maxAttempts);

        return $this;
    }

    public function getMaxAttempts(): int
    {
        return $this->inner->getMaxAttempts();
    }

    public function getPoolName(): string
    {
        if (method_exists($this->inner, 'getPoolName')) {
            return $this->inner->getPoolName();
        }
        return 'default';
    }
}
