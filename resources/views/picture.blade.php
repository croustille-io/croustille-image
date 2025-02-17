@if(isset($sources) && count($sources) > 1)
    <picture>
        @isset($sources)
            @foreach($sources as $source)
                <source
                    @if(isset($source['type']) && config('twill-image.webp_support'))
                        type="{{ $source['type'] }}"
                    @endif
                    @if(isset($source['mediaQuery']))
                        media="{{ $source['mediaQuery'] }}"
                    @endif
                    srcset="{{ $source['srcset'] }}"
                    @if(isset($sizes))
                        sizes="{{ $sizes }}"
                    @endif
                />
            @endforeach
        @endisset

        @include('twill-image::image', [
            'src' => $fallback,
        ])
    </picture>
@elseif(count($sources) === 1)
    @php
    $source = $sources[0];
    @endphp

    @include('twill-image::image', [
        'src' => $fallback,
        'type' => isset($source['type']) && config('twill-image.webp_support') ? $source['type'] : null,
        'media' => isset($source['mediaQuery']) ? $source['mediaQuery'] : null,
        'srcSet' => $source['srcset'],
    ])
@else
    @include('twill-image::image', [
        'src' => $fallback,
    ])
@endif
