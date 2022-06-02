@php
    /**
     * @var \Illuminate\View\ComponentAttributeBag $attributes
     * @var \Illuminate\Support\ViewErrorBag $errors
     * @var \Illuminate\Support\HtmlString $slot
    */
@endphp
<form {{ $attributes->merge(['class' => $errors->isEmpty() ? 'needs-validation' : '']) }} novalidate>
    @csrf
    {{ $slot }}
</form>
