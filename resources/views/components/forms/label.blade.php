@props(['value', 'forColumn' => false])
@php
/**
 * @var \Illuminate\View\ComponentAttributeBag $attributes
 * @var \Illuminate\Support\ViewErrorBag $errors
 * @var \Illuminate\Support\HtmlString $slot
 * @var bool $forColumn
*/
@endphp
<label {{ $attributes->merge(['class' => $forColumn ? 'col-form-label' : 'form-label']) }}>
    {{ $value ?? $slot }}
</label>
