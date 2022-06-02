@props(['prepend', 'append'])
@php
/**
 * @var \Illuminate\View\ComponentAttributeBag $attributes
 * @var \Illuminate\Support\ViewErrorBag $errors
 * @var \Illuminate\Support\HtmlString $slot
*/

$name = preg_replace(['/\[/', '/\]/'], ['.', ''], $attributes->get('name'));
$hasError = $errors->has($name);
if ($hasError) {
    $attributes = $attributes->merge([
        'aria-describedby' => 'validationFeedback' . ucfirst($name),
    ]);
}
if (isset($prepend)) {
    $attributes = $attributes->merge([
        'aria-describedby' => 'prepend' . ucfirst($name),
    ]);
}
if (isset($append)) {
    $attributes = $attributes->merge([
        'aria-describedby' => 'append' . ucfirst($name),
    ]);
}
@endphp
<!--suppress HtmlFormInputWithoutLabel -->
@if (isset($prepend) || isset($append))
    <div class="input-group {{ $hasError ? 'has-validation' : '' }}">
        @endif
        @if (isset($prepend))
            <span class="input-group-text" id="prepend{{ ucfirst($name) }}">{{ $prepend }}</span>
        @endif
        <input {{ $attributes->class(['form-control', 'form-control-'.$attributes->get('type'), 'is-invalid' => $hasError]) }}>
        @if (isset($append))
            <span class="input-group-text" id="append{{ ucfirst($name) }}">{{ $append }}</span>
        @endif
        <div class="invalid-feedback" id="validationFeedback{{ ucfirst($name) }}">
            @if($hasError)
                {{ $errors->first($name) }}
            @endIf
        </div>
        @if (isset($prepend) || isset($append))
    </div>
@endif
