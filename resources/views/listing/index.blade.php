@php
/**
 * @var Collection|Listing[]|LengthAwarePaginator $items
 * @var Builder $query
 * @var array $filters
 * @var bool $isSubscribed
 */

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

$config = [
    'columns' => [
        'id' => [
            'title' => __('#'),
            'sortable' => true,
            'filterable' => true,
        ],
        'latitude' => false,
        'longitude' => false,
        'url' => false,
        'city' => [
            'title' => __('City'),
            'sortable' => true,
            'filterable' => ['type' => 'select', 'values' => $filters['city'], 'label' => __('All cities')],
        ],
        'county' => [
            'title' => __('County'),
            'sortable' => true,
            'filterable' => ['type' => 'select', 'values' => $filters['county'], 'label' => __('All counties'), 'multiple' => true],
        ],
        'state' => [
            'title' => __('State'),
            'sortable' => true,
            'filterable' => ['type' => 'select', 'values' => $filters['state'], 'label' => __('All states'), 'multiple' => true],
        ],
        'area' => [
            'title' => __('Area (acres)'),
            'sortable' => true,
            'filterable' => true,
        ],
        'price' => [
            'title' => __('Price'),
            'sortable' => true,
            'filterable' => true,
            'value' => static fn (Listing $item) => number_format($item->price),
        ],
        'price_per_acre' => [
            'title' => __('Price/Acre'),
            'sortable' => true,
            'filterable' => true,
            'value' => static fn (Listing $item) => number_format($item->price_per_acre),
        ],
        'local_avg_price_per_acre' => [
            'title' => __('AVG/Acre'),
            'sortable' => true,
            'filterable' => true,
            'value' => static fn (Listing $item) => number_format($item->local_avg_price_per_acre),
        ],
        'local_min_price_per_acre' => [
            'title' => __('MIN/Acre'),
            'sortable' => true,
            'filterable' => true,
            'value' => static fn (Listing $item) => number_format($item->local_min_price_per_acre),
        ],
        'price_to_local_avg' => [
            'title' => __('Price/AVG (%)'),
            'sortable' => true,
            'filterable' => true,
            'value' => static fn (Listing $item) => ($item->price_to_local_avg !== null ? number_format($item->price_to_local_avg * 100, 2) . '%' : ''),
        ],
        'price_to_local_min' => [
            'title' => __('Price/MIN (%)'),
            'sortable' => true,
            'filterable' => true,
            'value' => static fn (Listing $item) => ($item->price_to_local_min !== null ? number_format($item->price_to_local_min * 100, 2) . '%' : ''),
        ],
        'status' => [
            'title' => __('Status'),
            'sortable' => true,
            'filterable' => ['type' => 'select', 'values' => $filters['status'], 'label' => __('All statuses'), 'multiple' => true],
            'value' => static fn (Listing $item) => $item->getStatus(),
        ],
        'updated_at' => [
            'title' => __('Updated'),
            'sortable' => true,
            'filterable' => ['type' => 'date', 'label' => __('All dates')],
            'value' => static fn (Listing $item) => $item->updated_at->translatedFormat('d M Y'),
        ],
    ],
    'controls' => [
        'buttons' => [
            'subscribe' => [
                'url' => route($isSubscribed ? 'listings.unsubscribe' : 'listings.subscribe', request()->only(['filter'])),
                'class' => 'btn btn-success' . ($isSubscribed ? ' btn-unsubscribe' : ' btn-subscribe'),
                'label' => $isSubscribed ? __('Unsubscribe') : __('Subscribe'),
                'icon' => $isSubscribed ? 'bi-bell-slash' : 'bi-bell',
                'disabled' => !request()->has('filter')
            ],
            'download' => [
                'url' => route('listings.download', request()->only(['filter', 'sort', 'direction'])),
                'class' => 'btn btn-primary',
                'label' => __('Export to spreadsheet'),
                'icon' => 'bi-download',
            ],
        ],
    ],
    'actions' => [
        'show' => [
            'route' => [
                'name' => 'listing',
                'params' => static fn (Listing $item) => ['listing' => $item->id],
            ],
            'class' => 'btn btn-sm btn-success',
            'label' => __('Show listing'),
            'icon' => 'bi-eye',
        ],
        'map' => [
            'url' => static fn (Listing $item) => "https://www.google.com/maps/search/?api=1&query=$item->latitude,$item->longitude",
            'disabled' => static fn (Listing $item) => !$item->hasCoordinates(),
            'class' => 'btn btn-sm btn-info',
            'label' => __('Show on map'),
            'icon' => 'bi-pin-map',
            'attributes' => [
                'target' => '_blank',
            ],
        ],
        'visit' => [
            'url' => static fn (Listing $item) => $item->url,
            'class' => 'btn btn-sm btn-secondary',
            'label' => __('Visit URL'),
            'icon' => 'bi-link',
            'attributes' => [
                'target' => '_blank',
            ],
        ],
    ],
];
@endphp
<!--suppress CheckEmptyScriptTag, HtmlUnknownTag, XmlUnboundNsPrefix, HtmlFormInputWithoutLabel -->
<x-app-layout>
    <x-slot name="header">{{ __('Listings') }}</x-slot>
    <x-data-grid::grid :query="$query" :config="$config" />

    <div class="modal fade" id="subscribeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Subscription name') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="name" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-danger modal-confirm" data-bs-dismiss="modal">{{ __('Confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
