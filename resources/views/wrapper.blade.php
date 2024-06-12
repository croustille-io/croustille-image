@php
$needSizer = $needSizer ?? false;
$needPlaceholder = $needPlaceholder ?? false;
@endphp

@if($needSizer || $needPlaceholder)
<div
    class="{{$wrapperClasses}}"
    style="{{$wrapperStyles}}"
    data-twill-image-wrapper
>
@endif
    @if($needPlaceholder)
        @include('twill-image::placeholder')
    @endif
    @include('twill-image::main-image')
@if($needSizer || $needPlaceholder)
</div>
@endif
