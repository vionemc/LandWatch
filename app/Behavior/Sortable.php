<?php

declare(strict_types=1);

namespace App\Behavior;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

use function array_filter;
use function count;
use function explode;
use function str_contains;
use function trim;

trait Sortable
{
    /**
     * Scope a query to apply sorting.
     *
     * @param Builder $query
     * @return Builder
     * @throws Exception
     */
    public function scopeSortable(Builder $query): Builder
    {
        $request = request();

        if ($request->query->has('sort')) {
            return $this->orderQueryBuilder($query, $request->except(['page']));
        }

        return $query->orderBy('created_at', 'desc')->orderBy('updated_at', 'desc');
    }

    private function orderQueryBuilder(Builder $query, array $params): Builder
    {
        /** @var Model $model */
        $model = $this;

        $column = $params['sort'] ?? null;
        if ($column === null) {
            return $query;
        }
        $direction = $params['direction'] ?? 'asc';

        $relation = null;
        // Check if relation is used for sort
        if (str_contains($column, '.')) {
            $explodedResult = explode('.', $column);
            if (count($explodedResult) !== 2) {
                throw new Exception('Only table and direct relation columns can be used for sorting.');
            }
            [$relationName, $column] = $explodedResult;

//            try {
            $relation = $query->getRelation($relationName);
            $relatedTable = $relation->getRelated()->getTable();
            $parentTable = $relation->getParent()->getTable();

            if ($parentTable === $relatedTable) {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $query = $query->from($parentTable, 'parent_' . $parentTable);
                $parentTable = 'parent_' . $parentTable;
                $relation->getParent()->setTable($parentTable);
            }

            if ($relation instanceof HasOne) {
                $relatedPrimaryKey = $relation->getQualifiedForeignKeyName();
                $parentPrimaryKey = $relation->getQualifiedParentKeyName();
            } elseif ($relation instanceof BelongsTo) {
                $relatedPrimaryKey = $relation->getQualifiedOwnerKeyName();
                $parentPrimaryKey = $relation->getQualifiedForeignKeyName();
            } else {
                throw new Exception();
            }

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $query = $query->select($parentTable . '.*')->leftJoin(
                $relatedTable,
                $parentPrimaryKey,
                '=',
                $relatedPrimaryKey
            );
            // We are sorting here, as we would in case of HasMany, because we are not sure on one
            // TODO: find a way to check that
            // https://reinink.ca/articles/ordering-database-queries-by-relationship-columns-in-laravel#ordering-by-has-one-relationships
            if ($relation instanceof HasOne) {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $query = $query->groupBy($parentPrimaryKey);
            }

            $model = $relation->getRelated();
//            }catch (RelationNotFoundException $e) {
//            } catch (Exception $e) {
//            }
        }

        if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column)) {
            // See previous comment
            if ($relation instanceof HasOne) {
                $column = '`' . $model->getTable() . '`' . '.' . '`' . $column . '`';
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $query = $query->orderByRaw("max($column) $direction");
            } else {
                $column = $model->getTable() . '.' . $column;
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $query = $query->orderBy($column, $direction);
            }
        } else {
            $columns =  array_filter($query->toBase()->columns, static fn ($queryColumn) => $queryColumn instanceof Expression);
            foreach ($columns as $value) {
                $segments = explode(' ', (string) $value);
                if (count($segments) > 1 && $segments[count($segments) - 2] === 'as' && trim($segments[count($segments) - 1], '`') === $column) {
                    /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                    $query = $query->orderBy($column, $direction);
                }
            }
        }

        return $query;
    }
}
