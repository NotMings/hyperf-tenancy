<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Mockery;
use NotMings\Tenancy\Contracts\TenantResolverInterface;
use NotMings\Tenancy\Middleware\TenantMiddleware;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 * @coversNothing
 */
class TenantMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        TenantContext::clear();
    }

    public function testProcessResolvesAndClearsContext(): void
    {
        $resolver = new class implements TenantResolverInterface {
            public function resolve(): void
            {
                TenantContext::setId('tenant-x');
            }
        };

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')->with(TenantResolverInterface::class)->andReturn(true);
        $container->shouldReceive('get')->with(TenantResolverInterface::class)->andReturn($resolver);

        $middleware = new TenantMiddleware($container);

        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->andReturn($response);

        $result = $middleware->process($request, $handler);
        $this->assertSame($response, $result);

        // context cleared
        $this->assertNull(TenantContext::getId());
    }

    public function testProcessWithoutResolverDoesNotError(): void
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')->with(TenantResolverInterface::class)->andReturn(false);

        $middleware = new TenantMiddleware($container);
        $request = Mockery::mock(ServerRequestInterface::class);
        $response = Mockery::mock(ResponseInterface::class);
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->andReturn($response);

        $result = $middleware->process($request, $handler);
        $this->assertSame($response, $result);
    }
}
