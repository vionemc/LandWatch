<?php

declare(strict_types=1);

namespace App\Behavior;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

use function array_filter;
use function array_map;
use function count;
use function date;
use function explode;
use function in_array;
use function json_decode;
use function ltrim;
use function preg_match;
use function str_contains;
use function strtotime;
use function substr;

use function trim;

use const ARRAY_FILTER_USE_KEY;

trait Filterable
{
    /**
     * Scope a query to apply sorting.
     *
     * @param Builder $query
     * @param array $except
     * @return Builder
     */
    public function scopeFilterable(Builder $query, array $except = []): Builder
    {
        $request = request();

        if ($request->query->has('filter')) {
            $params = array_filter($request->except(['page'])['filter'], static function ($key) use ($except) {
                return !in_array($key, $except, true);
            }, ARRAY_FILTER_USE_KEY);
            return $this->filterQueryBuilder($query, $params);
        }

        return $query;
    }

    private function filterQueryBuilder(Builder $query, array $filters): Builder
    {
        $model = $query->getModel();

        foreach ($filters as $column => $value) {
            $relation = null;
            $relationName = null;

            if (str_contains($column, '.')) {
                $explodedResult = explode('.', $column);
                if (count($explodedResult) !== 2) {
                    throw new Exception('Only table and direct relation columns can be used for sorting.');
                }
                [$relationName, $column] = $explodedResult;

                $relation = $query->getRelation($relationName);
                $model = $relation->getRelated();
            }

            $allowedOperators = ['>', '<', '=', '>=', '<=', '!=', '<>'];
            $singleCharOperator = $value[0];
            $twoCharOperator = substr($value, 0, 2);

            if ($value !== null && Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column)) {
                /**
                 * @throws \JsonException
                 */
                $callback = static function (Builder $query) use ($column, $value, $allowedOperators, $twoCharOperator, $singleCharOperator) {
                    if (str_contains($value, '|')) {
                        $values = array_map(
                            static fn(string $value) => substr($value, 1),
                            explode(' | ', $value),
                        );
                        $query->whereIn($column, $values);
                    } elseif(in_array($twoCharOperator, $allowedOperators, true)) {
                        $query->where($column, $twoCharOperator, ltrim(substr($value, 2)));
                    } elseif (in_array($singleCharOperator, $allowedOperators, true)) {
                        $value = ltrim(substr($value, 1));
                        // unescape escaped string
                        if (preg_match('/(?<=^\')[^\']*(?=\'$)/', $value, $matches)) {
                            $value = $matches[0];
                        }
                        $query->where($column, $singleCharOperator, $value);
                    } elseif ($singleCharOperator === '[') {
                        // We have JSON with multiple values
                        $values = array_map(
                            static fn(string $value) => substr($value, 1),
                            json_decode($value, false, 2, JSON_THROW_ON_ERROR)
                        );
                        $query->whereIn($column, $values);
                    } elseif (preg_match('/\d{2}\/\d{2}\/\d{4}-\d{2}\/\d{2}\/\d{4}/', $value) === 1) {
                        // We have date range
                        $dates = array_map(static fn(string $value) => strtotime($value), explode('-', $value));
                        if ($dates[0] === $dates[1]) {
                            $query->where(new Expression("DATE(`$column`)"), '=', date('Y-m-d', $dates[0]));
                        } else {
                            $query->whereBetween(new Expression("DATE(`$column`)"), [date('Y-m-d H:i:s', $dates[0]), date('Y-m-d H:i:s', $dates[1])]);
                        }
                    } else {
                        $query->where($column, 'like', "%$value%");
                    }
                };

                if ($relation === null) {
                    $query = $query->where($callback);
                } else {
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $query = $query->whereHas($relationName, $callback);
                }
            } else {
                $columns =  array_filter($query->toBase()->columns ?? [], static fn ($queryColumn) => $queryColumn instanceof Expression);
                foreach ($columns as $expression) {
                    $segments = explode(' ', (string) $expression);
                    if (count($segments) > 1 && $segments[count($segments) - 2] === 'as' && trim($segments[count($segments) - 1], '`') === $column) {
                        if(in_array($twoCharOperator, $allowedOperators, true)) {
                            $query->having($column, $twoCharOperator, ltrim(substr($value, 2)));
                        } elseif (in_array($singleCharOperator, $allowedOperators, true)) {
                            $query->having($column, $singleCharOperator, ltrim(substr($value, 1)));
                        } else {
                            $query->having($column, 'like', "%$value%");
                        }
                    }
                }
            }
        }

        return $query;
    }
}
