<?php

declare(strict_types=1);

namespace NotMings\Tenancy;

use Hyperf\Context\Context;
use NotMings\Tenancy\Enums\TenantContextKey;

class TenantContext
{
    public static function setId(?string $tenantId): void
    {
        Context::set(TenantContextKey::TENANT_ID->value, $tenantId);
    }

    public static function getId(): ?string
    {
        return Context::get(TenantContextKey::TENANT_ID->value);
    }

    public static function setDatabaseConfig(array $config): void
    {
        Context::set(TenantContextKey::TENANT_DATABASE_CONFIG->value, $config);
    }

    public static function getDatabaseConfig(): array
    {
        return Context::get(TenantContextKey::TENANT_DATABASE_CONFIG->value) ?? [];
    }

    public static function setRedisConfig(array $config): void
    {
        Context::set(TenantContextKey::TENANT_REDIS_CONFIG->value, $config);
    }

    public static function getRedisConfig(): array
    {
        return Context::get(TenantContextKey::TENANT_REDIS_CONFIG->value) ?? [];
    }

    public static function clear(): void
    {
        Context::set(TenantContextKey::TENANT_ID->value, null);
        Context::set(TenantContextKey::TENANT_DATABASE_CONFIG->value, null);
        Context::set(TenantContextKey::TENANT_REDIS_CONFIG->value, null);
    }
}
