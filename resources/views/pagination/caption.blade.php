@php
/**
 * @var \Illuminate\Pagination\LengthAwarePaginator $paginator
 */

$pageStart = number_format(($paginator->currentPage() * $paginator->perPage()) - $paginator->perPage() + 1);
$pageEnd = number_format($paginator->currentPage() * $paginator->perPage());
$pageEnd = $pageEnd > $paginator->total() ? $paginator->total() : $pageEnd;
$perPage = request()->input('per-page', '50');
$query = request()->except('per-page');
@endphp
<form class="d-flex align-items-center justify-content-between table-controls" method="GET" action="{{ request()->path() }}">
    @foreach ($query as $key => $value)
        @if (is_array($value))
            @foreach ($value as $arrKey => $arrValue)
                <input type="hidden" name="{{ $key }}[{{ $arrKey }}]" value="{{ $arrValue }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
    <span>
        Showing {{ $pageStart }}-{{ $pageEnd }} of {{ number_format($paginator->total()) }}
    </span>
    <div>
        {{ __('Number of rows') }}
        <span class="d-inline-block">
            <select name="per-page" class="form-select form-select-sm" aria-label="{{ __('Number of rows per page') }}">
                <option value="25" @if ($perPage === '25') selected @endif>25</option>
                <option value="50" @if ($perPage === '50') selected @endif>50</option>
                <option value="100" @if ($perPage === '100') selected @endif>100</option>
            </select>
        </span>
        <span class="d-inline-block">
            <a href="{{ route('listings.download', request()->only(['filter', 'sort', 'direction'])) }}" class="btn btn-sm btn-primary"><span aria-hidden="true" class="bi-download"></span></a>
        </span>
    </div>
</form>
