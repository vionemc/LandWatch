@props(['route', 'icon'])
@php
/**
 * @var \Illuminate\View\ComponentAttributeBag $attributes
 * @var \Illuminate\Support\ViewErrorBag $errors
 * @var \Illuminate\Support\HtmlString $slot
 * @var string $route
 * @var string $icon
 */

$route = $route ?? null;
$attrs = ($icon ?? false)
    ? ['href' => $route !== null ? route($route) : '#', 'active' => $route !== null && request()->routeIs($route), 'icon' => $icon]
    : ['href' => route($route), 'active' => request()->routeIs($route)];
@endphp
<!--suppress HtmlUnknownTag -->
<li class="nav-item">
    <x-nav-link {{ $attributes->merge($attrs) }}>
        {{ $slot }}
    </x-nav-link>
</li>
