<?php

declare(strict_types=1);

namespace App\DataGrid\View\Components;

use App\DataGrid\GridHelpers as Helpers;
use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;
use Laratips\Filterable\FilteringScope;
use Laratips\Sortable\SortingScope;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function __;
use function array_column;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_callable;
use function view;

final class Grid extends Component
{
    private EloquentBuilder|DatabaseBuilder $query;

    private bool $paginate;
    private array $config;
    private UrlGenerator $urlGenerator;
    /** @var Collection */
    private Collection $models;

    public Request $request;

    public array $rows = [];
    public ?Paginator $paginator = null;

    public bool $hasFilters = false;
    public array $filters = [];

    public bool $hasActions = false;
    public array $actions = [];

    public string $currentUrl;
    public ?string $keyName = null;

    /**
     * Create the component instance.
     *
     * @param EloquentBuilder|DatabaseBuilder $query
     * @param Request $request
     * @param UrlGenerator $urlGenerator
     * @param bool $paginate
     * @param array $config
     */
    public function __construct(EloquentBuilder|DatabaseBuilder $query, Request $request, UrlGenerator $urlGenerator, bool $paginate = true, array $config = [])
    {
        $this->query = $query;
        $this->paginate = $paginate;
        $this->config = $config;
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;
        $this->currentUrl = $request->url();
        $this->actions = $this->config['actions'] ?? [];
        $this->hasActions = count($this->actions) > 0;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function render(): string|Closure|View
    {
        $this->initRows();

        return view('data-grid.grid', [
            'columns' => $this->config['columns'] ?? [],
            'controls' => $this->config['controls'] ?? [],
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function initRows(): void
    {
        // Index columns if present
        // Index array
        if (array_key_exists('columns', $this->config) && Helpers::isSequentialArray($this->config['columns'])) {
            $attributes = array_column($this->config['columns'], 'attribute');
            $result = [];
            foreach ($this->config['columns'] as $key => $value) {
                $result[$attributes[$key]] = $value;
            }
            $this->config['columns'] = $result;
        }

        $this->setSelectColumns();

        if ($this->isFilterable()) {
            $this->initFilters();
            // Apply filterable scope
            (new FilteringScope())->apply($this->query, $this->query->getModel());
        }

        // Apply sortable scope
        (new SortingScope())->apply($this->query, $this->query->getModel());

        if ($this->paginate) {
            $this->paginator = $this->query->paginate();
            $this->models = Collection::wrap($this->paginator->items());
        } else {
            $this->models = $this->query->get();
            // $this->rows = $this->query->get()->toArray();
        }
        if ($this->models->count() > 0) {
            /** @var Model $model */
            $model = $this->models[0];
            $this->keyName = $model->getKeyName();
        }
        $this->rows = $this->models->toArray();

        // Check if there are dynamic columns
        $hasDynamicColumns = count(array_filter(
                array_column(array_values($this->config['columns']), 'value'),
                static fn ($value) => is_callable($value)
            )) > 0;
        if ($hasDynamicColumns) {
            foreach ($this->rows as $key => &$row) {
                foreach (array_filter($this->config['columns'], 'is_array') as $column => $spec) {
                    if (array_key_exists('value', $spec) && is_callable($spec['value'])) {
                        $row[$column] = $spec['value']($this->models[$key]);
                    }
                }
            }
            unset($row);
        }

        $this->initSorting();

        if ($this->hasActions && count($this->rows) > 0) {
            $this->initActions();
        }
    }

    private function initActions(): void
    {
        foreach ($this->actions as &$action) {
            // Check if action is dynamic
            if (array_key_exists('url', $action) && is_callable($action['url'])) {
                $action['dynamic'] = true;
            } elseif (array_key_exists('route', $action) && is_callable($action['route']['params'] ?? [])) {
                $action['dynamic'] = true;
            } elseif (array_key_exists('disable', $action) && is_callable($action['disabled'])) {
                $action['dynamic'] = true;
            } else {
                $action['dynamic'] = false;
            }

            $action['attributes'] = $action['attributes'] ?? [];
            $action['attributes']['title'] = $action['label'];

            if (array_key_exists('icon', $action)) {
                $action['attributes']['aria-label'] = $action['label'];
            }

            if (isset($action['route']['method'])) {
                $action['form']['class'] = 'd-inline-block';
                $action['route']['method'] = Str::lower($action['route']['method']);
            }

            if (array_key_exists('confirm', $action)) {
                if (array_key_exists('form', $action)) {
                    $action['form']['class'] .= ' needs-confirmation';
                }
                $action['class'] = ($action['class'] ?? '') . ' needs-confirmation';
            }

            if ($action['dynamic'] === false) {
                // $url = $action['url'] ?? '#';

                if (array_key_exists('route', $action)) {
                    $action['url'] = $this->urlGenerator->route($action['route']['name'], $action['route']['params'] ?? []);
                }

                if (array_key_exists('disabled', $action)) {
                    $action['class'] = ($action['class'] ?? '') . ' disabled';
                    $action['attributes']['tabindex'] = '-1';
                    $action['attributes']['aria-disabled'] = 'true';
                }
            }
        }
        unset($action);

        // If we have dynamic actions, pre-built them for each row
        $dynamicActions = array_filter($this->actions, static fn (array $action) => $action['dynamic'] === true);
        if (count($dynamicActions) > 0) {
            foreach ($this->rows as $key => &$row) {
                foreach ($dynamicActions as $name => $action) {
                    $row['actions'][$name] = [];

                    $url = '#';
                    if (array_key_exists('url', $action)) {
                        if (is_callable($action['url'])) {
                            $url = $action['url']($this->models[$key]);
                        } else {
                            $url = $action['url'];
                        }
                    } elseif (array_key_exists('route', $action) && is_callable($action['route']['params'] ?? [])) {
                        $url = $this->urlGenerator->route($action['route']['name'], $action['route']['params']($this->models[$key]));
                    }
                    $row['actions'][$name]['url'] = $url;

                    if (array_key_exists('disabled', $action) && (is_callable($action['disabled']) && $action['disabled']($this->models[$key]))) {
                        $row['actions'][$name]['class'] = ($action['class'] ?? '') . ' disabled';
                        $row['actions'][$name]['attributes'] = $action['attributes'];
                        $row['actions'][$name]['attributes']['tabindex'] = '-1';
                        $row['actions'][$name]['attributes']['aria-disabled'] = 'true';
                    }
                }
            }
        }
    }

    private function initFilters(): void
    {
        $columns = array_filter(
            array_filter($this->config['columns'], 'is_array'),
            static fn(array $column) => isset($column['filterable']) && $column['filterable'] !== false
        );
        $appliedFilters = $this->request->get('filter', []);

        foreach ($columns as $column => $spec) {
            $filter = $spec['filterable'];
            if ($filter === true) {
                $this->filters[$column] = ['type' => 'text', 'label' => $spec['title'] ?? $column];
            } elseif (is_array($filter)) {
                if (isset($filter['type']) || isset($filter['label']) || isset($filter['values'])) {
                    $type = array_key_exists('type', $filter) ? $filter['type'] : 'text';
                    $label = array_key_exists('label', $filter) ? $filter['label'] : ($spec['title'] ?? $column);
                    $values = array_key_exists('values', $filter) ? $filter['values'] : [];
                    $multiple = array_key_exists('multiple', $filter) ? $filter['multiple'] : false;

                    $this->filters[$column] = [
                        'type' => count($values) > 0 ? 'select' : $type,
                        'label' => $label,
                        'values' => $values,
                        'multiple' => $multiple,
                    ];
                } else {
                    $this->filters[$column]['type'] = 'select';
                    $this->filters[$column]['label'] = __('Select ') . $spec['title'] ?? $column;
                    $this->filters[$column]['values'] = $filter;
                }
            }

            $this->filters[$column]['value'] = array_key_exists($column, $appliedFilters) ? $appliedFilters[$column] : '';
            $this->filters[$column]['name'] = "filter[$column]";
        }

        if (count($this->filters) > 0) {
            $this->hasFilters = true;
        }
    }

    private function isFilterable(): bool
    {
        $columns = $this->config['columns'];
        // Gel all active filters
        $filters = array_filter(array_column($columns, 'filterable'), static fn($value) => $value !== false);

        return count($filters) > 0;
    }

    private function initSorting(): void
    {
        // TODO: refactor
        $query = $this->request->all();
        $question = $this->request->getBaseUrl() . $this->request->getPathInfo() === '/' ? '/?' : '?';
        if ($this->paginate && $this->paginator instanceof AbstractPaginator) {
            unset($query[$this->paginator->getPageName()]);
        }
        $defaultSortIcon = 'bi-arrow-down-up';
        $sortedColumns = [];
        foreach ($this->query->getQuery()->orders ?? [] as $order) {
            if (isset($order['column'])) {
                $sortedColumns[$order['column']] = $order['direction'];
            }
        }

        if (array_key_exists('columns', $this->config)) {
            foreach ($this->config['columns'] as $column => &$spec) {
                if (isset($spec['sortable']) && $spec['sortable'] === true) {
                    $icon = null;
                    $columnQuery = $query;
                    $columnQuery['sort'] = $column;
                    $isSorted = (isset($query['sort']) && $query['sort'] === $column) || (isset($sortedColumns[$column]));
                    if ($isSorted && (isset($columnQuery['direction']) || isset($sortedColumns[$column]))) {
                        $columnQuery['direction'] = ($columnQuery['direction'] ?? $sortedColumns[$column]) === 'asc' ? 'desc' : 'asc';
                        $icon = $columnQuery['direction'] === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
                    } else {
                        $columnQuery['direction'] = 'asc';
                    }

                    $url = $this->request->url() . $question . Arr::query($columnQuery);
                    $spec['sort']['url'] = $url;
                    $spec['sort']['icon'] = $icon ?? $defaultSortIcon;
                }
            }
        }
    }

    private function setSelectColumns(): void
    {
        if (array_key_exists('columns', $this->config)) {
            $query = $this->query->getQuery();

            if ($query->columns !== null && $query->columns[0] !== $query->from . '.*') {
                return;
            }

            unset($query->columns[0]);
            $columns = array_filter(
                array_keys($this->config['columns']),
                static fn (string $column) => !str_contains($column, '.')
            );
            $model = $this->query->getModel();
            foreach ($this->query->getEagerLoads() as $name => $constraints) {
                if (!str_contains($name, '.')) {
                    $relation = $this->query->getRelation($name);
                    if ($relation instanceof HasOneOrMany && $relation->getRelated() instanceof $model) {
                        $columns[] = $relation->getLocalKeyName();
                    } elseif ($relation instanceof BelongsTo) {
                        $columns[] = $relation->getForeignKeyName();
                    }
                }
            }

            $tableColumns = Schema::connection($model->getConnectionName())->getColumnListing($model->getTable());

            //$aliases = [];
            //$expressions = \array_filter($this->query->toBase()->columns ?? [], static fn ($queryColumn) => $queryColumn instanceof Expression);
            //foreach ($expressions as $expression) {
            //    $segments = explode(' ', (string) $expression);
            //    if (\count($segments) > 1 && $segments[count($segments) - 2] === 'as') {
            //        $aliases[\trim($segments[\count($segments) - 1], '`')] = $expression;
            //        //$aliases[] = \trim($segments[\count($segments) - 1], '`');
            //    }
            //}

            foreach ($columns as $column) {
                if (in_array($column, $tableColumns, true)) {
                    $query->columns[] = $column;
                }
            }

//            $relations = \array_keys($this->query->getEagerLoads());
//            $select = [];
//
//            foreach (\array_keys($this->config['columns']) as $column) {
//                if (($pos = \strpos($column, '.')) !== false && \in_array(substr($column, 0, $pos), $relations, true)) {
//                    continue;
//                }
//
//                if (\in_array($column, \array_keys($aliases), true)) {
//                    $select[] = $aliases[$column];
//                } else {
//                    $select[] = $column;
//                }
//            }

//            return \array_filter(
//                \array_keys($this->config['columns']),
//                static fn ($column) => !in_array($column, $aliases, true) && strpos($column, '.') === false
//            );
        }
    }
}
