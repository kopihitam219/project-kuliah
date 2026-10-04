{{--
    Logo + nama usaha dari menu Settings.
    Variabel opsional: $iconClass, $textClass, $accentClass
--}}
@php
    [$brandFirst, $brandMiddle, $brandLast] = \App\Support\Brand::nameParts();
    $brandLogo = \App\Support\Brand::logoUrl();
@endphp

@if ($brandLogo)
    <img src="{{ $brandLogo }}" alt="" class="{{ $iconClass ?? '' }}" style="height: 34px; width: auto; max-width: 120px; object-fit: contain">
@else
    <span class="{{ $iconClass ?? '' }}" aria-hidden="true">⛳</span>
@endif
<span class="{{ $textClass ?? '' }}">{{ $brandFirst }}@if ($brandMiddle) <span class="{{ $accentClass ?? '' }}">{{ $brandMiddle }}</span>@endif{{ $brandLast ? ' ' . $brandLast : '' }}</span>
