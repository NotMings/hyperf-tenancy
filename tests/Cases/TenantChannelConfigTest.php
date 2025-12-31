<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\AsyncQueue\Exception\InvalidQueueException;
use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\Queue\TenantChannelConfig;
use NotMings\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TenantChannelConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function testGetChannelWithoutPrefix(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $orig */
        $orig = $container->get(ConfigInterface::class);
        $container->set(ConfigInterface::class, new Config([
            'redis' => $orig->get('redis', []),
            'databases' => $orig->get('databases', []),
            'tenancy' => [
                'queue' => [
                    'prefix' => [
                        'enabled' => false,
                        'format' => 'tenancy:%s:%s',
                    ],
                ],
            ],
        ]));

        $cfg = new TenantChannelConfig('jobs');
        $this->assertSame('jobs', $cfg->getChannel());
        $this->assertSame('jobs:waiting', $cfg->getWaiting());
        $this->assertSame('jobs:reserved', $cfg->getReserved());
        $this->assertSame('jobs:timeout', $cfg->getTimeout());
        $this->assertSame('jobs:delayed', $cfg->getDelayed());
        $this->assertSame('jobs:failed', $cfg->getFailed());
    }

    public function testGetChannelWithPrefixAndTenant(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $orig */
        $orig = $container->get(ConfigInterface::class);
        $container->set(ConfigInterface::class, new Config([
            'redis' => $orig->get('redis', []),
            'databases' => $orig->get('databases', []),
            'tenancy' => [
                'queue' => [
                    'prefix' => [
                        'enabled' => true,
                        'format' => 'tenancy:%s:%s',
                    ],
                ],
            ],
        ]));

        TenantContext::setId('t-1');
        $cfg = new TenantChannelConfig('jobs');

        $channel = 'tenancy:t-1:jobs';
        $this->assertSame($channel, $cfg->getChannel());
        $this->assertSame($channel . ':waiting', $cfg->getWaiting());
        $this->assertSame($channel . ':reserved', $cfg->getReserved());
        $this->assertSame($channel . ':timeout', $cfg->getTimeout());
        $this->assertSame($channel . ':delayed', $cfg->getDelayed());
        $this->assertSame($channel . ':failed', $cfg->getFailed());
    }

    public function testGetMethodWithPrefixAndTenant(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $orig */
        $orig = $container->get(ConfigInterface::class);
        $container->set(ConfigInterface::class, new Config([
            'redis' => $orig->get('redis', []),
            'databases' => $orig->get('databases', []),
            'tenancy' => [
                'queue' => [
                    'prefix' => [
                        'enabled' => true,
                        'format' => 'tenancy:%s:%s',
                    ],
                ],
            ],
        ]));

        TenantContext::setId('t-2');
        $cfg = new TenantChannelConfig('jobs');

        $channel = 'tenancy:t-2:jobs';
        $this->assertSame($channel . ':waiting', $cfg->get('waiting'));
        $this->assertSame($channel . ':reserved', $cfg->get('reserved'));
        $this->assertSame($channel . ':delayed', $cfg->get('delayed'));
        $this->assertSame($channel . ':failed', $cfg->get('failed'));
        $this->assertSame($channel . ':timeout', $cfg->get('timeout'));

        $this->expectException(InvalidQueueException::class);
        $cfg->get('unknown');
    }

    public function testGetMethodWithoutPrefixNoTenant(): void
    {
        $container = ApplicationContext::getContainer();
        /** @var ConfigInterface $orig */
        $orig = $container->get(ConfigInterface::class);
        $container->set(ConfigInterface::class, new Config([
            'redis' => $orig->get('redis', []),
            'databases' => $orig->get('databases', []),
            'tenancy' => [
                'queue' => [
                    'prefix' => [
                        'enabled' => false,
                        'format' => 'tenancy:%s:%s',
                    ],
                ],
            ],
        ]));

        TenantContext::clear();
        $cfg = new TenantChannelConfig('jobs');

        $this->assertSame('jobs:waiting', $cfg->get('waiting'));
        $this->assertSame('jobs:reserved', $cfg->get('reserved'));
        $this->assertSame('jobs:delayed', $cfg->get('delayed'));
        $this->assertSame('jobs:failed', $cfg->get('failed'));
        $this->assertSame('jobs:timeout', $cfg->get('timeout'));

        $this->expectException(InvalidQueueException::class);
        $cfg->get('unknown');
    }
}
