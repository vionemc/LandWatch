<!-- /resources/views/components/data/controls.blade.php -->
@props([
'paginator' => null,
'showSummary' => true,
'showPager' => true,
'buttons' => [],
])
@php
    /**
     * @var ComponentAttributeBag $attributes
     * @var HtmlString $slot
     * @var LengthAwarePaginator|null $paginator
     * @var array $buttons
     * @var bool $showSummary
     * @var bool $showPager
     */

    use Illuminate\Pagination\LengthAwarePaginator;
    use Illuminate\Support\HtmlString;
    use Illuminate\View\ComponentAttributeBag;

    $query = request()->except('per-page');
@endphp
<form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center justify-content-between grid-controls">
    @foreach ($query as $key => $value)
        @if (is_array($value))
            @foreach ($value as $arrKey => $arrValue)
                <input type="hidden" name="{{ $key }}[{{ $arrKey }}]" value="{{ $arrValue }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    @if ($showPager && $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
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

    @if ($showSummary && $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->total() > 0)
        <small class="grid-summary position-absolute start-50 translate-middle-x">
            {{ __('Showing :start-:end of :total', ['start' => number_format($paginator->firstItem()), 'end' => number_format($paginator->lastItem()), 'total' => number_format($paginator->total())]) }}
        </small>
    @endif

    @if (count($buttons) > 0)
        <div class="grid-buttons ms-auto">
            @foreach ($buttons as $key => $button)
                <a
                    role="button"
                    href="{{ $button['url'] }}"
                    class="{{ $button['class'] }}@if (isset($button['disabled']) && $button['disabled'] === true) disabled @endif"
                    title="{{ $button['label'] }}"
                    @if (isset($button['icon'])) aria-label="{{ $button['label'] }}" @endif
                    @if (isset($button['disabled']) && $button['disabled'] === true) aria-disabled="true" tabindex="-1" @endif>
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
