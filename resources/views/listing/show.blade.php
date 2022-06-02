@php
/**
 * @var \App\Models\Listing $item
 */
@endphp
<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<x-app-layout>
    <x-slot name="header">{{ __('Listing') }} {{ $item->id }}</x-slot>
    <table class="table table-striped table-bordered table-hover table-large-padding">
        <tbody>
        <tr>
            <th scope="row">{{ __('URL') }}</th>
            <td><a href="{{ $item->url }}" target="_blank">{{ $item->url }}</a></td>
        </tr>
        <tr>
            <th scope="row">{{ __('Types') }}</th>
            <td>{{ $item->types }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Address') }}</th>
            <td>{{ $item->address }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('City') }}</th>
            <td>{{ $item->city }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('County') }}</th>
            <td>{{ $item->county }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('State') }}</th>
            <td>{{ $item->state }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Zip') }}</th>
            <td>{{ $item->zip }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Price') }}</th>
            <td>${{ number_format($item->price) }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Area') }}</th>
            <td>{{ $item->area }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Price / Acre') }}</th>
            <td>${{ number_format($item->price_per_acre) }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('AVG / acre') }}</th>
            <td>{{ $item->local_avg_price_per_acre !== null ? '$' . number_format($item->local_avg_price_per_acre) : '' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('MEDIAN / acre') }}</th>
            <td>{{ $item->local_median_price_per_acre !== null ? '$' . number_format($item->local_median_price_per_acre) : '' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('MIN / acre') }}</th>
            <td>{{ $item->local_min_price_per_acre !== null ? '$' . number_format($item->local_min_price_per_acre) : '' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Price / AVG (%)') }}</th>
            <td>{{ $item->price_to_local_avg !== null ? number_format($item->price_to_local_avg * 100, 2) . '%' : '' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Price / MIN (%)') }}</th>
            <td>{{ $item->price_to_local_min !== null ? number_format($item->price_to_local_min * 100, 2) . '%' : '' }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Status') }}</th>
            <td>{{ $item->getStatus() }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Created At') }}</th>
            <td>{{ $item->created_at->format('d M Y') }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Update At') }}</th>
            <td>{{ $item->updated_at->format('d M Y') }}</td>
        </tr>
        <tr>
            <th scope="row">{{ __('Checked At') }}</th>
            <td>{{ $item->checked_at->format('d M Y') }}</td>
        </tr>
        </tbody>
    </table>
    <form action="{{ route('listing.store') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $item->id }}">
        <textarea name="notes" class="form-control mb-2" cols="30" rows="10" aria-label="{{ __('Listing notes') }}" placeholder="{{ __('Listing notes') }}">{{ $item->notes }}</textarea>
        <div class="text-end">
            <button type="submit" class="btn btn-primary mb-3">{{ __('Save') }}</button>
        </div>
    </form>
</x-app-layout>
