<?php

declare(strict_types=1);

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Pool\DbPool;
use Hyperf\DbConnection\Pool\PoolFactory;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\Di\Definition\DefinitionSourceInterface;
use Hyperf\Redis\Pool\PoolFactory as RedisPoolFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define BASE_PATH for components expecting it.
if (! defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Minimal config used in tests.
$arrayConfig = [
    'redis' => [
        'default' => [
            'host' => '127.0.0.1',
            'port' => 6380,
            'db' => 0,
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 1,
            ],
        ],
    ],
    'databases' => [
        'default' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            // Pool settings retained for consistency; FakeDbPool avoids real connections.
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 1,
            ],
        ],
    ],
    'tenancy' => [
        'queue' => [
            'prefix' => [
                'enabled' => false,
                'format' => 'tenancy:%s:%s',
            ],
        ],
    ],
];

$config = new Config($arrayConfig);

// Test doubles used by RedisDriver & others
class NullEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        return $event;
    }
}

class NullLogger implements StdoutLoggerInterface
{
    public function emergency(string|Stringable $message, array $context = []): void {}

    public function alert(string|Stringable $message, array $context = []): void {}

    public function critical(string|Stringable $message, array $context = []): void {}

    public function error(string|Stringable $message, array $context = []): void {}

    public function warning(string|Stringable $message, array $context = []): void {}

    public function notice(string|Stringable $message, array $context = []): void {}

    public function info(string|Stringable $message, array $context = []): void {}

    public function debug(string|Stringable $message, array $context = []): void {}

    public function log($level, string|Stringable $message, array $context = []): void {}
}

// Fake DbPool that avoids real DB connections
class FakePooledConnection implements ConnectionInterface
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function reconnect(): bool
    {
        return true;
    }

    public function check(): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function release(): void {}
}

class FakeDbPool extends DbPool
{
    public function __construct(PsrContainerInterface $container, string $name)
    {
        // Do not call parent to avoid validations.
        $this->container = $container;
        $this->name = $name;
    }

    public function get(): ConnectionInterface
    {
        // Return a fake pooled connection whose getConnection returns a mock of Database ConnectionInterface
        $mock = Mockery::mock(Hyperf\Database\ConnectionInterface::class);
        return new FakePooledConnection($mock);
    }
}

// In-memory fake Redis proxy to avoid native extension dependency.
class FakeRedisProxyMemory extends RedisProxy
{
    private array $lists = [];

    private array $zsets = [];

    public function __construct() {}

    public function lPush(string $key, string $value): int
    {
        $this->lists[$key] = $this->lists[$key] ?? [];
        array_unshift($this->lists[$key], $value);
        return count($this->lists[$key]);
    }

    public function lLen(string $key): int
    {
        return isset($this->lists[$key]) ? count($this->lists[$key]) : 0;
    }

    public function zAdd(string $key, float|int $score, string $value): int
    {
        $this->zsets[$key] = $this->zsets[$key] ?? [];
        $this->zsets[$key][] = [$score, $value];
        return 1;
    }

    public function zCard(string $key): int
    {
        return isset($this->zsets[$key]) ? count($this->zsets[$key]) : 0;
    }

    public function del(string $key): int
    {
        unset($this->lists[$key], $this->zsets[$key]);
        return 1;
    }
}

class FakeRedisFactory
{
    public function __construct(private FakeRedisProxyMemory $proxy) {}

    public function get(string $poolName): RedisProxy
    {
        return $this->proxy;
    }
}

// Build DI container with minimal definitions.
/** @var DefinitionSourceInterface $definitionSource */
$definitionSource = new DefinitionSource([
    // Map DbPool::class to FakeDbPool via factory (accept parameter array)
    DbPool::class => function (PsrContainerInterface $c, array $params): DbPool {
        $name = is_array($params) ? ($params['name'] ?? 'default') : (string) $params;
        return new FakeDbPool($c, $name);
    },
]);

$container = new Container($definitionSource);

// Prime common singletons
$container->set(ConfigInterface::class, $config);
$container->set(EventDispatcherInterface::class, new NullEventDispatcher());
$container->set(StdoutLoggerInterface::class, new NullLogger());
// Allow make() helpers to work
$container->set(Container::class, $container);
$container->set(PsrContainerInterface::class, $container);

// Some components fetch RedisFactory directly
// (removed) Do not instantiate real RedisFactory here.
// Redis PoolFactory for RedisProxy autowiring
// Provide a fake RedisFactory using in-memory proxy; avoid Redis extension.
$container->set(RedisFactory::class, new FakeRedisFactory(new FakeRedisProxyMemory()));
// (Optional) Real RedisPoolFactory is not needed when using fake factory, but keep mapping for completeness.
$container->set(RedisPoolFactory::class, new RedisPoolFactory($container));
// PoolFactory uses container to create DbPool; leave default autowire behavior
$container->set(PoolFactory::class, new PoolFactory($container));

ApplicationContext::setContainer($container);
