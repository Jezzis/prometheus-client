<?php

namespace Krenor\Prometheus\Metrics;

use Krenor\Prometheus\Contracts\Metric;
use Tightenco\Collect\Support\Collection;
use Krenor\Prometheus\Exceptions\LabelException;
use Krenor\Prometheus\Contracts\Types\Observable;

abstract class Summary extends Metric implements Observable
{
    /**
     * @var int[]
     */
    protected $quantiles = [
        .01,
        .05,
        .5,
        .9,
        .99,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        foreach ($this->labels as $label) {
            if (preg_match('/^quantile$/', $label)) {
                throw new LabelException('The label `quantile` is used internally to designate summary quantiles.');
            }
        }

        sort($this->quantiles);
    }

    /**
     * {@inheritdoc}
     */
    final public function type(): string
    {
        return 'summary';
    }

    /**
     * {@inheritdoc}
     */
    public function observe(float $value, array $labels): self
    {
        static::$storage->observe($this, $value, $labels);

        return $this;
    }

    /**
     * @return Collection
     */
    public function quantiles(): Collection
    {
        return new Collection($this->quantiles);
    }
}
