<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Contracts;

interface TenantResolverInterface
{
    public function resolve(): void;
}
