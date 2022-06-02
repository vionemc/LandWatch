<!-- /resources/views/components/data/grid.blade.php -->
@props([
'paginator' => null,
'columns' => [],
'actions' => [],
'controls' => [],
'filters' => [],
])
@php
    /**
     * @var ComponentAttributeBag $attributes
     * @var HtmlString $slot
     * @var LengthAwarePaginator|null $paginator
     * @var array $controls
     * @var array $columns
     * @var array $actions
     * @var array $filters
     */
    use App\Behavior\Sortable;
    use Illuminate\Pagination\LengthAwarePaginator;
    use Illuminate\Support\HtmlString;
    use Illuminate\View\ComponentAttributeBag;

    $query = request()->all();
    $is_sortable = false;
    if ($paginator !== null && count($paginator->items()) > 0) {
        $is_sortable = isset(class_uses_recursive($paginator->items()[0])[Sortable::class]);
    }
@endphp
<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<div class="card data-grid">
    <div class="card-header px-2 text-white bg-dark">
        <x-data.controls :paginator="$paginator" :buttons="$controls['buttons'] ?? []" :showSummary="$controls['showSummary'] ?? true" :showPager="$controls['showPager'] ?? true" />
    </div>
    <div class="card-body bg-light p-0">
        <div class="table-responsive">
            @if (count($filters) > 0)
                @php
                    /**
                     * @var array $query
                     * @noinspection PhpFullyQualifiedNameUsageInspection
                     */

                    $currentQuery = $query;
                    unset($currentQuery['page'], $currentQuery['filter']);
                @endphp
                <form method="GET" action="{{ request()->url() }}" class="grid-filters-form d-none">
                    @foreach ($currentQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                </form>
            @endif
            <table class="table table-sm table-striped table-hover align-middle mb-0">
                <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col" @if (count($paginator->items()) > 0 && $column['attribute'] === $paginator->items()[0]->getKeyName()) style="width: 1px; min-width: 60px; white-space: nowrap;" @endif>
                            @if ($is_sortable && isset($column['sortable']) && $column['sortable'] === true)
                                @php
                                    /**
                                     * @var array $query
                                     * @var array $column
                                     * @var \Illuminate\Pagination\LengthAwarePaginator $paginator
                                     * @noinspection PhpFullyQualifiedNameUsageInspection
                                     */

                                    $icon = 'bi-arrow-down-up';
                                    $sortQuery = $query;
                                    $sortQuery['sort'] = $column['attribute'];
                                    unset($sortQuery[$paginator->getPageName()]);
                                    $isSorted = isset($query['sort']) && $query['sort'] === $column['attribute'];
                                    if ($isSorted && isset($sortQuery['direction'])) {
                                        $sortQuery['direction'] = $sortQuery['direction'] === 'asc' ? 'desc' : 'asc';
                                        $icon = $sortQuery['direction'] === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
                                    } else {
                                        $sortQuery['direction'] = 'asc';
                                    }
                                    $question = request()->getBaseUrl() . request()->getPathInfo() === '/' ? '/?' : '?';
                                    $url = count($sortQuery) > 0 ? request()->url() . $question . \Illuminate\Support\Arr::query($sortQuery) : request()->fullUrl();
                                @endphp
                                <a class="d-flex align-items-center justify-content-between text-nowrap link-dark text-decoration-none" href="{{ $url }}">{{ $column['label'] }}<small class="ms-1 {{ $icon }}"></small></a>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                    @if (count($actions) > 0 || count($filters) > 0)
                        <th class="actions" scope="col">{{ __('Actions') }}</th>
                    @endif
                </tr>
                @if (count($filters) > 0)
                    <tr class="grid-filters">
                        @foreach ($columns as $column)
                            <th scope="col">
                                @if (isset($filters[$column['attribute']]))
                                    <div class="grid-filter">
                                        @php
                                            /**
                                             * @var array $filters
                                             * @var array $column
                                             * @var array $query
                                             * @noinspection PhpFullyQualifiedNameUsageInspection
                                             */

                                            $filter = $filters[$column['attribute']];
                                            $name = 'filter[' . ($filter['attribute'] ?? $column['attribute']) . ']';
                                            $value = $query['filter'][$column['attribute']] ?? '';
                                            $label = $filter['label'] ?? '';
                                        @endphp
                                        @if ($filter['type'] === 'text')
                                            <input type="text" class="form-control form-control-sm" name="{{ $name }}" @if ($label !== '') title="{{ $label }}" aria-label="{{ $label }}" placeholder="{{ $label }}" @endif value="{{ $value }}">
                                        @elseif ($filter['type'] === 'date')
                                            <input type="text" class="form-control form-control-sm date-picker" name="{{ $name }}" @if ($label !== '') title="{{ $label }}" aria-label="{{ $label }}" placeholder="{{ $label }}" @endif value="{{ $value }}">
                                        @elseif ($filter['type'] === 'select')
                                            <select name="{{ $name }}" class="form-select form-select-sm" @if ($label !== '') aria-label="{{ $label }}" @endif @if (isset($filter['multiple']) && $filter['multiple'] === true) multiple="multiple" data-placeholder="{{ $label !== '' ? $label : __('Select ') . $column['label'] }}" @endif>
                                                @if (!isset($filter['multiple']) || (isset($filter['multiple']) && $filter['multiple'] === false))
                                                    <option value="" @if ($value === '') selected @endif>{{ $label !== '' ? $label : __('Select ') . $column['label'] }}</option>
                                                    @foreach ($filter['values'] as $key => $text)
                                                        <option value="={{ $key }}" @if (substr($value, 1) === (string) $key) selected @endif>{{ $text }}</option>
                                                    @endforeach
                                                @else
                                                    @php
                                                        /**
                                                         * @var string $value
                                                         */
                                                        $values = array_map(
                                                            static fn(string $value) => substr($value, 1),
                                                            $value !== '' ? json_decode($value, false, 2, JSON_THROW_ON_ERROR) : []
                                                        );
                                                    @endphp
                                                    @foreach ($filter['values'] as $key => $text)
                                                        <option value="={{ $key }}" @if (in_array((string) $key, $values, true)) selected @endif>{{ $text }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        @endif
                                    </div>
                                @endif
                            </th>
                        @endforeach
                        <th class="actions" scope="col">
                            <div class="d-flex align-items-center justify-content-between">
                                <a tabindex="0" class="btn btn-sm btn-success btn-filters-info" role="button" data-bs-container="body" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="left" data-bs-html="true" title="Filter Guide" data-bs-content="You can use following operators for input filters: <strong>></strong>, <strong><</strong>, <strong>>=</strong>, <strong><=</strong>, <strong>!=</strong> or <strong><></strong>.">
                                    <span aria-hidden="true" class="bi-question-lg"></span>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-filters-reset" aria-label="{{ __('Reset filters') }}">
                                    <span aria-hidden="true" class="bi-x-lg"></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-primary btn-filters-apply" aria-label="{{ __('Apply filters') }}">
                                    <span aria-hidden="true" class="bi-funnel-fill"></span>
                                </button>
                            </div>
                        </th>
                    </tr>
                @endif
                </thead>
                <tbody>
                @foreach ($paginator->items() as $item)
                    <tr>
                        @foreach ($columns as $column)
                            @if (isset($column['value']) && is_callable($column['value']))
                                @php
                                    /**
                                     * @var array $column
                                     * @var \Illuminate\Database\Eloquent\Model $item
                                     * @noinspection PhpFullyQualifiedNameUsageInspection
                                     */
                                    $value = $column['value']($item)
                                @endphp
                            @else
                                @php
                                    /**
                                     * @var array $column
                                     * @var array $item
                                     * @noinspection PhpFullyQualifiedNameUsageInspection
                                     */
                                    $value = \Illuminate\Support\Arr::get($item, $column['attribute']);
                                @endphp
                            @endif
                            @if ($column['attribute'] === $item->getKeyName())
                                <th scope="row">{!! $value ?? '' !!}</th>
                            @else
                                <td>{!! $value ?? '' !!}</td>
                            @endif
                        @endforeach
                        @if (count($actions) > 0 || count($filters) > 0)
                            <th class="actions" scope="row">
                                <div class="d-flex align-items-center justify-content-between">
                                    @foreach ($actions as $key => $action)
                                        @php
                                            /**
                                             * @var \Illuminate\Database\Eloquent\Model $item
                                             * @var array $action
                                             * @noinspection PhpFullyQualifiedNameUsageInspection
                                             */
                                            $url = '#';
                                            if (isset($action['url'])) {
                                                if (is_callable($action['url'])) {
                                                    $url = $action['url']($item);
                                                } else {
                                                    $url = $action['url'];
                                                }
                                            } elseif (isset($action['route'])) {
                                                $params = [];
                                                if (isset($action['route']['params'])) {
                                                    if (is_array($action['route']['params'])) {
                                                        $params = $action['route']['params'];
                                                    } elseif (is_callable($action['route']['params'])) {
                                                        $params = $action['route']['params']($item);
                                                    }
                                                }
                                                $url = route($action['route']['name'], $params);
                                            }

                                            $actionAttributes = [
                                                'title' => $action['label'],
                                            ];
                                            if (isset($action['icon'])) {
                                                $actionAttributes['aria-label'] = $action['label'];
                                            }
                                            if (isset($action['attributes'])) {
                                                $actionAttributes = array_merge($action['attributes'], $actionAttributes);
                                            }
                                            if (isset($action['disabled']) && $action['disabled']($item)) {
                                                $action['class'] = ($action['class'] ?? '') . ' disabled';
                                                $actionAttributes['tabindex'] = '-1';
                                                $actionAttributes['aria-disabled'] = 'true';
                                            }
                                        @endphp
                                        @if (isset($action['route']['method']))
                                            @php
                                                /**
                                                 * @var array $action
                                                 * @noinspection PhpFullyQualifiedNameUsageInspection
                                                 */
                                                $method = \Illuminate\Support\Str::lower($action['route']['method']);
                                            @endphp
                                            <form action="{{ $url }}" method="{{ $method === 'get' ? 'get' : 'post' }}" class="{{ implode(' ', array_merge(['d-inline-block'], isset($action['confirm']) ? ['needs-confirmation'] : [])) }}" @if (isset($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif>
                                                @csrf
                                                @if (in_array($method, ['put', 'patch', 'delete']))
                                                    @method($method)
                                                    <button type="submit" class="{{ $action['class'] ?? '' }}" @foreach ($actionAttributes as $key => $attr) {!! $key . '="' . $attr . '"' !!} @endforeach>
                                                        @if (isset($action['icon']))
                                                            <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                                                        @else
                                                            {{ $action['label'] }}
                                                        @endif
                                                    </button>
                                                @endif
                                            </form>
                                        @else
                                            <a href="{{ $url }}" class="{{ implode(' ', array_merge(isset($action['class']) ? [$action['class']] : [], isset($action['confirm']) ? ['needs-confirmation'] : [])) }}" role="button" @foreach ($actionAttributes as $key => $attr) {!! $key . '="' . $attr . '"' !!} @endforeach @if (isset($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif>
                                                @if (isset($action['icon']))
                                                    <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                                                @else
                                                    {{ $action['label'] }}
                                                @endif
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </th>
                        @endif
                    </tr>
                @endforeach
                </tbody>
                @if ($paginator !== null && $paginator->hasPages())
                    <tfoot>
                    <tr>
                        <td colspan="{{ (count($actions) > 0 || count($filters) > 0) ? count($columns) + 1 : count($columns) }}">
                            {{ $paginator->onEachSide(1)->withQueryString()->links('pagination.bootstrap-5') }}
                        </td>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Are you sure?') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger modal-confirm" data-bs-dismiss="modal">{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>
