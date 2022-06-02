<?php

declare(strict_types=1);

/**
 * @var Collection|User[]|LengthAwarePaginator $items
 * @var array $filters
 */

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

$question = request()->getBaseUrl() . request()->getPathInfo() === '/' ? '/?' : '?';
$query = request()->except(['page', 'filter']);
$url = count($query) > 0 ? request()->url() . $question . Arr::query($query) : request()->fullUrl();

$columns = [
    ['label' => '#', 'attribute' => 'id', 'sortable' => true,],
    ['label' => __('Name'), 'attribute' => 'name', 'sortable' => true],
    ['label' => __('Email'), 'attribute' => 'email', 'sortable' => true],
    ['label' => __('Password'), 'attribute' => 'password', 'value' => static fn () => '(secret)', 'sortable' => false],
    ['label' => __('Created'), 'attribute' => 'created_at', 'value' => static fn (User $item) => $item->created_at->translatedFormat('d M Y'), 'sortable' => true],
];

$controls = [
    'buttons' => [
        'create' => [
            'url' => route('users.create'),
            'class' => 'btn btn-primary',
            'label' => __('Create user'),
            'icon' => 'bi-plus-lg',
        ],
    ],
];

$filters = [
    'name' => ['type' => 'text', 'label' => __('Name')],
    'email' => ['type' => 'text', 'label' => __('Email')],
];

$actions = [
    'edit' => [
        'route' => [
            'name' => 'users.edit',
            'params' => static fn (User $item) => ['user' => $item->id],
        ],
        'class' => 'btn btn-success',
        'label' => __('Edit user'),
        'icon' => 'bi-pencil',
    ],
    'destroy' => [
        'route' => [
            'name' => 'users.destroy',
            'method' => 'delete',
            'params' => static fn (User $item) => ['user' => $item->id],
        ],
        'class' => 'btn btn-danger',
        'label' => __('Delete user'),
        'icon' => 'bi-trash',
        'confirm' => __('Are you sure you want to delete this record? The process cannot be undone.'),
    ],
];
?>
<!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
<x-app-layout>
    <x-slot name="header">{{ __('Users') }}</x-slot>
    <x-data.grid :paginator="$items" :columns="$columns" :filters="$filters" :actions="$actions" :controls="$controls" />
</x-app-layout>
