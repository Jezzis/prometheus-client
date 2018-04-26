<?php

namespace Krenor\Prometheus\Storage\Repositories;

use Predis\Client as Redis;
use Tightenco\Collect\Support\Collection;
use Krenor\Prometheus\Contracts\Repository;

class RedisRepository implements Repository
{
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * RedisRepository constructor.
     *
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): Collection
    {
        return new Collection(
            strpos($key, ':VALUES') === false
                ? $this->redis->hgetall($key)
                : $this->redis->lrange($key, 0, -1)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, string $field, float $value): void
    {
        $this->redis->hincrbyfloat($key, $field, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, string $field, float $value): void
    {
        $this->increment($key, $field, -abs($value));
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, string $field, $value, $override = true): void
    {
        $override
            ? $this->redis->hset($key, $field, $value)
            : $this->redis->hsetnx($key, $field, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function push(string $key, float $value): void
    {
        $this->redis->rpush($key, $value);
    }
}
