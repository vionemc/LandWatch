<!-- /resources/views/datagrid/grid.blade.php -->
<?php
/**
 * @var ComponentAttributeBag $attributes
 * @var Paginator|null $paginator
 * @var Request $request
 * @var array $columns
 * @var bool $hasFilters
 * @var array $filters
 * @var bool $hasActions;
 * @var array $actions
 * @var array $controls
 * @var string $currentUrl;
 * @var string $keyName;
 */

use Illuminate\Contracts\Pagination\Paginator;use Illuminate\Http\Request;use Illuminate\View\ComponentAttributeBag;

?>
<!--suppress XmlUnboundNsPrefix -->
<div {{ $attributes->class(['card data-grid']) }}>
    <div class="card-header px-2 text-white bg-dark">
        <form action="{{ $currentUrl }}" method="GET" class="d-flex align-items-center justify-content-between grid-controls">
            @foreach ($request->except('per-page') as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $arrayKey => $arrayValue)
                        <input type="hidden" name="{{ $key }}[{{ $arrayKey }}]" value="{{ $arrayValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            @if ($paginator instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="grid-pager">
                    <label for="per-page">{{ __('Show:') }}</label>
                    <span class="d-inline-block">
                        <select name="per-page" id="per-page" class="form-select d-inline-block" aria-label="{{ __('Number of rows per page') }}">
                            <option value="25" @if ($paginator->perPage() === 25) selected @endif>25</option>
                            <option value="50" @if ($paginator->perPage() === 50) selected @endif>50</option>
                            <option value="100" @if ($paginator->perPage() === 100) selected @endif>100</option>
                        </select>
                    </span>
                </div>
            @endif

            @if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $paginator->total() > 0)
                <small class="grid-summary position-absolute start-50 translate-middle-x">
                    {{ __('Showing :start-:end of :total', ['start' => number_format($paginator->firstItem()), 'end' => number_format($paginator->lastItem()), 'total' => number_format($paginator->total())]) }}
                </small>
            @endif

            @if (array_key_exists('buttons', $controls) && count($controls['buttons']) > 0)
                <div class="grid-buttons ms-auto">
                    @foreach ($controls['buttons'] as $button)
                        <a href="{{ $button['url'] }}" class="{{ $button['class'] }}" title="{{ $button['label'] }}" @if (isset($button['icon'])) aria-label="{{ $button['label'] }}" @endif>
                            @if (isset($button['icon']))
                                <small class="{{ $button['icon'] }}" aria-hidden="true"></small>
                            @else
                                {{ $button['label'] }}
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </form>
    </div>
    <div class="card-body bg-light p-0">
        <div class="table-responsive">
            @if ($hasFilters)
                <form action="{{ $currentUrl }}" method="GET" class="grid-filters-form d-none">
                    @foreach($request->except($paginator instanceof \Illuminate\Pagination\AbstractPaginator ? [$paginator->getPageName(), 'filter'] : ['filter']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                </form>
            @endif
            <table class="table table-sm table-striped table-hover align-middle mb-0">
                <thead>
                <tr>
                    @foreach (array_filter($columns, 'is_array') as $column => $spec)
                        <th scope="col" @if ($column === $keyName && count($rows) > 0) style="width: 1px; min-width: 60px; white-space: nowrap;" @endif>
                            @if (isset($spec['sort']))
                                <x-sortable::link column="{{ $column }}" title="{{ $spec['title'] }}" type="default" :request="$request" :query="$request->except(['page'])" class="d-flex align-items-center justify-content-between text-nowrap text-decoration-none" />
                            @else
                                {{ $spec['title'] ?? $column }}
                            @endif
                        </th>
                    @endforeach
                    @if (count($actions) > 0 || count($filters) > 0)
                        <th class="actions" scope="col">{{ __('Actions') }}</th>
                    @endif
                </tr>
                @if ($hasFilters)
                    <tr class="grid-filters">
                        @foreach (array_filter($columns, 'is_array') as $column => $spec)
                            <th scope="col">
                                @if (array_key_exists($column, $filters))
                                    <div class="grid-filter">
                                        @if ($filters[$column]['type'] === 'text')
                                            <input type="text" class="form-control form-control-sm"
                                                   name="{{ $filters[$column]['name'] }}"
                                                   title="{{ $filters[$column]['label'] }}"
                                                   aria-label="{{ $filters[$column]['label'] }}"
                                                   placeholder="{{ $filters[$column]['label'] }}"
                                                   value="{{ $filters[$column]['value'] ?? '' }}" />
                                        @elseif ($filters[$column]['type'] === 'date')
                                            <input type="text" class="form-control form-control-sm date-picker"
                                                   name="{{ $filters[$column]['name'] }}"
                                                   title="{{ $filters[$column]['label'] }}"
                                                   aria-label="{{ $filters[$column]['label'] }}"
                                                   placeholder="{{ $filters[$column]['label'] }}"
                                                   value="{{ $filters[$column]['value'] ?? '' }}">
                                        @elseif ($filters[$column]['type'] === 'select')
                                            <select name="{{ $filters[$column]['name'] }}"
                                                    class="form-select form-select-sm"
                                                    title="{{ $filters[$column]['label'] }}"
                                                    aria-label="{{ $filters[$column]['label'] }}"
                                                    @if (isset($filters[$column]['multiple']) && $filters[$column]['multiple'] === true) multiple="multiple" data-placeholder="{{ $filters[$column]['label'] }}" @endif>
                                                @if (!isset($filters[$column]['multiple']) || (isset($filters[$column]['multiple']) && $filters[$column]['multiple'] === false))
                                                    <option value="" @if ($filters[$column]['value'] === '') selected @endif>{{ $filters[$column]['label'] }}</option>
                                                    @foreach ($filters[$column]['values'] as $key => $text)
                                                        <option value="={{ $key }}" @if (substr($filters[$column]['value'], 1) === (string) $key) selected @endif>{{ $text }}</option>
                                                    @endforeach
                                                @else
                                                    @php
                                                        /**
                                                         * @var string $column
                                                         * @var array $filters
                                                         */
                                                        $values = array_map(
                                                            static function(string $value) {
                                                                $value = substr($value, 1);
                                                                if (preg_match('/(?<=^\')[^\']*(?=\'$)/', $value, $matches)) {
                                                                    $value = $matches[0];
                                                                }
                                                                return $value;
                                                            },
                                                            $filters[$column]['value'] !== '' ? explode(' | ', $filters[$column]['value']) : []
                                                        );
                                                    @endphp
                                                    @foreach ($filters[$column]['values'] as $key => $text)
                                                        <option value="={{ is_numeric($key) ? $key : "'$key'" }}" @if (in_array((string) $key, $values, true)) selected @endif>{{ $text }}</option>
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
                @foreach ($rows as $row)
                    <tr>
                        @foreach (array_filter($columns, 'is_array') as $column => $spec)
                            @if ($column === $keyName)
                                <th scope="row">{!! \Illuminate\Support\Arr::get($row, $column) ?? '' !!}</th>
                            @else
                                <td>{!! \Illuminate\Support\Arr::get($row, $column) ?? '' !!}</td>
                            @endif
                        @endforeach
                        @if ($hasActions)
                            <th scope="row" class="actions">
                                <div class="d-flex align-items-center justify-content-between">
                                    @foreach ($actions as $name => $action)
                                        @if (isset($action['route']['method']))
                                            <form action="{{ $row['actions'][$name]['url'] ?? $action['url'] }}"
                                                  method="{{ $action['route']['method'] === 'get' ? 'get' : 'post' }}"
                                                  class="{{ $action['form']['class'] }}"
                                                  @if (isset($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif>
                                                @csrf
                                                @if (in_array($action['route']['method'], ['put', 'patch', 'delete']))
                                                    @method($action['route']['method'])
                                                    <button type="submit"
                                                            class="{{ $row['actions'][$name]['class'] ?? $action['class'] }}"
                                                    @foreach (($row['actions'][$name]['attributes'] ?? $action['attributes']) as $key => $attr) {!! "$key='$attr'" !!} @endforeach>
                                                        @if (array_key_exists('icon', $action))
                                                            <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                                                        @else
                                                            {{ $action['label'] }}
                                                        @endif
                                                    </button>
                                                @endif
                                            </form>
                                        @else
                                            <a href="{{ $row['actions'][$name]['url'] ?? $action['url'] }}"
                                               class="{{ $row['actions'][$name]['class'] ?? $action['class'] }}"
                                               role="button"
                                               @foreach (($row['actions'][$name]['attributes'] ?? $action['attributes']) as $key => $attr) {!! "$key='$attr'" !!} @endforeach
                                               @if (isset($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif>
                                                @if (array_key_exists('icon', $action))
                                                    <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                                                @else
                                                    {{ $action['label'] }}
                                                @endif
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </th>
                        @elseif ($hasFilters)
                            <td>&nbsp;</td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
                @if ($paginator !== null && $paginator->hasPages())
                    <tfoot>
                    <tr>
                        <td colspan="{{ ($hasActions || $hasFilters) ? count($columns) + 1 : count($columns) }}">
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
