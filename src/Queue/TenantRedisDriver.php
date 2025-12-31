<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Queue;

use Hyperf\AsyncQueue\Driver\RedisDriver as BaseRedisDriver;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\Exception\TenantQueueContextMissingException;
use NotMings\Tenancy\TenantContext;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

class TenantRedisDriver extends BaseRedisDriver
{
    public function __construct(ContainerInterface $container, $config)
    {
        parent::__construct($container, $config);

        $baseChannel = $this->channel->getChannel();
        $this->channel = make(TenantChannelConfig::class, [
            'baseChannel' => $baseChannel,
        ]);
    }

    public function push(JobInterface $job, int $delay = 0): bool
    {
        $wrapped = new TenantJob($job, TenantContext::getId(), TenantContext::getDatabaseConfig(), TenantContext::getRedisConfig());
        return parent::push($wrapped, $delay);
    }

    public function pop(): array
    {
        $this->ensureTenantContextForQueueOps();
        return parent::pop();
    }

    public function reload(?string $queue = null): int
    {
        $this->ensureTenantContextForQueueOps();
        return parent::reload($queue);
    }

    public function flush(?string $queue = null): bool
    {
        $this->ensureTenantContextForQueueOps();
        return parent::flush($queue);
    }

    public function info(): array
    {
        $this->ensureTenantContextForQueueOps();
        return parent::info();
    }

    private function ensureTenantContextForQueueOps(): void
    {
        /** @var ConfigInterface $config */
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $enablePrefix = (bool) $config->get('tenancy.queue.prefix.enabled', false);
        if ($enablePrefix && TenantContext::getId() === null) {
            throw new TenantQueueContextMissingException('Tenancy queue prefix is enabled but tenant context is missing. Set tenant context before queue operations.');
        }
    }
}
