<?php

namespace Krenor\Prometheus\Storage;

use Exception;
use Krenor\Prometheus\Metrics\Summary;
use Krenor\Prometheus\Contracts\Metric;
use Krenor\Prometheus\Metrics\Histogram;
use Krenor\Prometheus\Contracts\Storage;
use Tightenco\Collect\Support\Collection;
use Krenor\Prometheus\Contracts\Repository;
use Krenor\Prometheus\Contracts\Types\Settable;
use Krenor\Prometheus\Exceptions\LabelException;
use Krenor\Prometheus\Contracts\Types\Observable;
use Krenor\Prometheus\Exceptions\StorageException;
use Krenor\Prometheus\Contracts\Types\Incrementable;
use Krenor\Prometheus\Contracts\Types\Decrementable;
use Krenor\Prometheus\Storage\Concerns\StoresMetrics;

class StorageManager implements Storage
{
    use StoresMetrics;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * StorageManager constructor.
     *
     * @param Repository $repository
     * @param string|null $prefix
     */
    public function __construct(Repository $repository, ?string $prefix = 'PROMETHEUS')
    {
        $this->repository = $repository;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Metric $metric): Collection
    {
        $key = "{$this->prefix}:{$metric->key()}";

        try {
            $items = $this->repository->get($key);

            switch (true) {
                case $metric instanceof Histogram:
                    return $metric->builder($items->merge($this->repository->get("{$key}:SUM")))
                                  ->samples();
                case $metric instanceof Summary:
                    return $metric->builder($items->map(function (string $key) {
                        return $this->repository->get($key);
                    }))->samples();
                default:
                    return $metric->builder($items)
                                  ->samples();
            }
        } catch (Exception $e) {
            $class = get_class($metric);

            throw new StorageException("Failed to collect the samples of [{$class}]: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function increment(Incrementable $metric, float $value, array $labels = []): void
    {
        try {
            $this->repository->increment(
                "{$this->prefix}:{$metric->key()}",
                $this->labeled($metric, $labels)->toJson(),
                $value
            );
        } catch (LabelException $e) {
            throw $e;
        } catch (Exception $e) {
            $class = get_class($metric);

            throw new StorageException("Failed to increment [{$class}] by `{$value}`: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(Decrementable $metric, float $value, array $labels = []): void
    {
        try {
            $this->repository->decrement(
                "{$this->prefix}:{$metric->key()}",
                $this->labeled($metric, $labels)->toJson(),
                $value
            );
        } catch (LabelException $e) {
            throw $e;
        } catch (Exception $e) {
            $class = get_class($metric);

            throw new StorageException("Failed to decrement [{$class}] by `{$value}`: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function observe(Observable $metric, float $value, array $labels = []): void
    {
        $key = "{$this->prefix}:{$metric->key()}";
        $labeled = $this->labeled($metric, $labels);
        $field = $labeled->toJson();

        try {
            if ($metric instanceof Histogram) {
                $this->repository->increment($key, $labeled->merge($this->bucket($metric, $value))->toJson(), 1);
                $this->repository->increment("{$key}:SUM", $field, $value);
            }

            if ($metric instanceof Summary) {
                $identifier = "{$key}:" . crc32($field) . ':VALUES';

                $this->repository->set($key, $field, $identifier, false);
                $this->repository->push($identifier, $value);
            }
        } catch (Exception $e) {
            $class = get_class($metric);

            throw new StorageException("Failed to observe [{$class}] with `{$value}`: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set(Settable $metric, float $value, array $labels = []): void
    {
        try {
            $this->repository->set(
                "{$this->prefix}:{$metric->key()}",
                $this->labeled($metric, $labels)->toJson(),
                $value
            );
        } catch (LabelException $e) {
            throw $e;
        } catch (Exception $e) {
            $class = get_class($metric);

            throw new StorageException("Failed to set [{$class}] to `{$value}`: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @return bool
     */
    public function flush(): bool
    {
        return $this->repository->flush();
    }
}
