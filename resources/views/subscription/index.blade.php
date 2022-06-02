<?php

declare(strict_types=1);

/**
 * @var Collection|Subscription[]|LengthAwarePaginator $items
 * @var array $filters
 */

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

$question = request()->getBaseUrl() . request()->getPathInfo() === '/' ? '/?' : '?';
$query = request()->except(['page', 'filter']);
$url = count($query) > 0 ? request()->url() . $question . Arr::query($query) : request()->fullUrl();

$columns = [
    ['label' => '#', 'attribute' => 'id', 'sortable' => true,],
    ['label' => __('Name'), 'attribute' => 'name', 'sortable' => true,],
    ['label' => __('Filters'), 'attribute' => 'filters', 'value' => static function (Subscription $item) {
        return implode(', ', array_map(
            static fn(string $value, string $key) => ucfirst($key) . $value,
            $item->filters,
            array_keys($item->filters)
        ));
    }, 'sortable' => true],
    ['label' => __('Created'), 'attribute' => 'created_at', 'value' => static fn (Subscription $item) => $item->created_at->translatedFormat('d M Y'), 'sortable' => true],
];

$controls = [];

$filters = [];

$actions = [
    'show' => [
        'route' => [
            'name' => 'subscription.listings',
            'params' => static fn (Subscription $item) => ['subscription' => $item->id],
        ],
        'class' => 'btn btn-success',
        'label' => __('Show listing'),
        'icon' => 'bi-eye',
    ],
    'destroy' => [
        'route' => [
            'name' => 'subscriptions.destroy',
            'method' => 'delete',
            'params' => static fn (Subscription $item) => ['subscription' => $item->id],
        ],
        'class' => 'btn btn-danger',
        'label' => __('Remove subscription'),
        'icon' => 'bi-trash',
        'confirm' => __('Are you sure you want to delete this record? The process cannot be undone.'),
    ],
];
?>
<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<x-app-layout>
    <x-slot name="header">{{ __('Subscriptions') }}</x-slot>
    <x-data.grid :paginator="$items" :columns="$columns" :filters="$filters" :actions="$actions" :controls="$controls" />
</x-app-layout>
