<?php

declare(strict_types=1);

namespace NotMings\Tenancy\Queue;

use Hyperf\AsyncQueue\Driver\ChannelConfig as BaseChannelConfig;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use NotMings\Tenancy\TenantContext;

class TenantChannelConfig extends BaseChannelConfig
{
    public function __construct(private readonly string $baseChannel)
    {
        parent::__construct($baseChannel);
    }

    public function get(string $queue)
    {
        $channel = $this->getChannel();
        return match ($queue) {
            'waiting' => $this->derive($channel, 'waiting'),
            'reserved' => $this->derive($channel, 'reserved'),
            'delayed' => $this->derive($channel, 'delayed'),
            'failed' => $this->derive($channel, 'failed'),
            'timeout' => $this->derive($channel, 'timeout'),
            default => parent::get($queue),
        };
    }

    public function getChannel(): string
    {
        /** @var ConfigInterface $config */
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $enablePrefix = (bool) $config->get('tenancy.queue.prefix.enabled', false);

        if ($enablePrefix) {
            $format = (string) $config->get('tenancy.queue.prefix.format', 'tenancy:%s:%s');
            $tenantId = TenantContext::getId();
            if ($tenantId) {
                return sprintf($format, $tenantId, $this->baseChannel);
            }
        }

        return $this->baseChannel;
    }

    public function getWaiting(): string
    {
        return $this->derive($this->getChannel(), 'waiting');
    }

    public function getReserved(): string
    {
        return $this->derive($this->getChannel(), 'reserved');
    }

    public function getTimeout(): string
    {
        return $this->derive($this->getChannel(), 'timeout');
    }

    public function getDelayed(): string
    {
        return $this->derive($this->getChannel(), 'delayed');
    }

    public function getFailed(): string
    {
        return $this->derive($this->getChannel(), 'failed');
    }

    private function derive(string $channel, string $suffix): string
    {
        return $channel . ':' . $suffix;
    }
}
