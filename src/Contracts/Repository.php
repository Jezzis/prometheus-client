<?php

namespace Krenor\Prometheus\Contracts;

use Tightenco\Collect\Support\Collection;

interface Repository
{
    /**
     * @param string $key
     *
     * @return Collection
     */
    public function get(string $key): Collection;

    /**
     * @param string $key
     * @param string $field
     * @param float $value
     *
     * @return void
     */
    public function increment(string $key, string $field, float $value): void;

    /**
     * @param string $key
     * @param string $field
     * @param float $value
     *
     * @return void
     */
    public function decrement(string $key, string $field, float $value);

    /**
     * @param string $key
     * @param string $field
     * @param mixed $value
     * @param bool $override
     *
     * @return void
     */
    public function set(string $key, string $field, $value, $override = true);

    /**
     * @param string $key
     * @param float $value
     *
     * @return void
     */
    public function push(string $key, float $value);
}
