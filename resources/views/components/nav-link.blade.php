@props(['active', 'icon'])
@php
/**
 * @var \Illuminate\View\ComponentAttributeBag $attributes
 * @var \Illuminate\Support\ViewErrorBag $errors
 * @var \Illuminate\Support\HtmlString $slot
 * @var bool $active
 * @var string $icon
 */

$classes = ($active ?? false)
    ? 'nav-link active'
    : 'nav-link'
@endphp
<!--suppress HtmlUnknownTag -->
<a {{ $attributes->merge(['class' => $classes]) }} {{ $active ? 'aria-current="page"' : '' }}>
    {!! isset($icon) ? '<span class="bi ' . $icon . '"></span>' : '' !!}
    {{ $slot }}
</a>
