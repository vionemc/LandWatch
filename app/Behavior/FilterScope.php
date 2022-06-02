<?php

declare(strict_types=1);

namespace App\Behavior;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FilterScope implements Scope
{
    use Filterable;

    /**
     * @throws Exception
     */
    public function apply(Builder $builder, Model $model, array $filters = []): void
    {
        $this->filterQueryBuilder($builder, $filters);
    }
}
