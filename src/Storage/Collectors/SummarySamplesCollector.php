<?php

namespace Krenor\Prometheus\Storage\Collectors;

use Closure;
use Krenor\Prometheus\Sample;
use Krenor\Prometheus\Metrics\Summary;
use Tightenco\Collect\Support\Collection;

class SummarySamplesCollector extends SamplesCollector
{
    /**
     * @var Summary
     */
    protected $metric;

    /**
     * {@inheritdoc}
     */
    protected function group(): Collection
    {
        return parent::group()->map(function (Collection $stored) {
            return $stored->first();
        })->map(function (array $stored) {
            /** @var Collection $values */
            $values = $stored['value'];
            $count = $values->count();

            return $this->metric
                ->quantiles()
                ->map($this->calculate($values->sort()->values(), $count))
                ->push(compact('count'))
                ->push(['sum' => $values->sum()])
                ->map(function ($item) use ($stored) {
                    $item['labels'] = $stored['labels'];

                    return $item;
                });
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function sample(string $name): Closure
    {
        return function (array $item) use ($name) {
            $labels = new Collection($item['labels']);

            if (array_key_exists('count', $item)) {
                return new Sample("{$name}_count", $item['count'], $labels);
            }

            if (array_key_exists('sum', $item)) {
                return new Sample("{$name}_sum", $item['sum'], $labels);
            }

            return new Sample("{$name}", $item['value'], $labels->put('quantile', $item['quantile']));
        };
    }

    /**
     * @param Collection $values
     * @param int $count
     *
     * @return Closure
     */
    private function calculate(Collection $values, int $count): Closure
    {
        return function (float $quantile) use ($count, $values) {
            $index = $count * $quantile;

            $value = floor($index) === $index
                ? ($values[$index - 1] + $values[$index]) / 2
                : $values[floor($index)];

            return compact('quantile', 'value');
        };
    }
}
