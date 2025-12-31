<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Enums;

enum TenantContextKey: string
{
    case TENANT_ID = 'tenancy.tenant_id';
    case TENANT_DATABASE_CONFIG = 'tenancy.tenant_database_config';
    case TENANT_REDIS_CONFIG = 'tenancy.tenant_redis_config';
}
