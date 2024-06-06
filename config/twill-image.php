<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Low Quality Placeholder
    |--------------------------------------------------------------------------
    |
    | Tells if LQIP should be used if it is available.
    |
    */
    'lqip' => false,

    /*
    |--------------------------------------------------------------------------
    | Use image sizer element
    |--------------------------------------------------------------------------
    |
    | If sets to auto, the sizer will be used if LQIP is enabled.
    | It can be set to `true` or `false` to enable or disable.
    |
    */
    'image_sizer' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Enable WebP Support
    |--------------------------------------------------------------------------
    |
    | Add sources support for WepP images.
    |
    */
    'webp_support' => true,

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | Use inline styles for default styling or use classes instead.
    |
    | In the example below, classes are used for applying
    | Tailwind CSS classes.
    |
    */
    'mode' => 'inline-styles', // 'inline-styles' | 'classes' | 'both'

    'inline_styles' => [
        'main' => [
            'background-color' => '#e3e3e3',
            'height' => '100%',
            'margin' => 0,
            'max-width' => 'none',
            'padding' => 0,
            'width' => '100%',
            'object-fit' => 'cover',
            'object-position' => 'center',
        ],
        'wrapper' => [
            'position' => 'relative',
            'overflow' => 'hidden',
        ],
        'placeholder' => [
            'position' => 'absolute',
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'left' => 0,
        ],
    ],

    'classes' => [
        'main' => [
            'h-full',
            'm-0',
            'max-w-none',
            'p-0',
            'w-full',
            'object-cover',
            'object-center'
        ],
        'wrapper' => [
            'relative',
            'overflow-hidden',
        ],
        'placeholder' => [
            'absolute',
            'inset-0',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Presets
    |--------------------------------------------------------------------------
    |
    | Define image presets here.
    |
    */
    'presets' => [
        // Preset example
        // 'preview_image' => [
        //     'crop' => 'default',
        //     'sizes' => '25vw',
        // ],

        // Preset example with multiple crops
        // 'art_directed' => [
        //     'crop' => 'desktop',
        //     'width' => 700,
        //     'sizes' => '(max-width: 767px) 100vw, (min-width: 767px) and (max-width: 1023px) 50vw, 33vw',
        //     'sources' => [
        //         [
        //             'crop' => 'mobile',
        //             'media_query' => '(max-width: 767px)',
        //         ],
        //         [
        //             'crop' => 'tablet',
        //             'media_query' => '(min-width: 767px) and (max-width: 1023px)',
        //         ],
        //         [
        //             'crop' => 'desktop',
        //             'media_query' => '(min-width: 1024px)',
        //         ]
        //         // ...
        //     ],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Columns - Frontend breakpoints and grid structure
    |--------------------------------------------------------------------------
    |
    | Define the columns class that is used to dynamically generates
    | `sizes` and `media`.
    |
    */
    'columns_class' => A17\Twill\Image\Services\ImageColumns::class,

    /*
    |--------------------------------------------------------------------------
    | Static Images Local Path
    |--------------------------------------------------------------------------
    |
    | Define the local path where the static images
    | are located. This should correcponds to the Twill `ImageService`
    | source folder and be publicly available.
    |
    */
    'static_local_path' => public_path(),

    'static_image_support' => false,

    // Glide config overrides
    'glide' => [
        'source' => public_path(),
        'base_path' => 'static',
    ],

];
