<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Middleware;

use NotMings\Tenancy\Contracts\TenantResolverInterface;
use NotMings\Tenancy\TenantContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TenantMiddleware implements MiddlewareInterface
{
    public function __construct(private ContainerInterface $container) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var null|TenantResolverInterface $resolver */
        $resolver = $this->container->has(TenantResolverInterface::class)
            ? $this->container->get(TenantResolverInterface::class)
            : null;

        if ($resolver instanceof TenantResolverInterface) {
            $resolver->resolve();
        }

        try {
            return $handler->handle($request);
        } finally {
            TenantContext::clear();
        }
    }
}
