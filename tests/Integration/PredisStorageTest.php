<?php

namespace Krenor\Prometheus\Tests\Integration;

use Predis\Client as Redis;
use Krenor\Prometheus\Storage\Redis\PredisConnection;
use Krenor\Prometheus\Storage\Repositories\RedisRepository;

class PredisStorageTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $redis = new Redis([
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
        ]);

        self::$repository = new RedisRepository(
            new PredisConnection($redis)
        );
    }
}
