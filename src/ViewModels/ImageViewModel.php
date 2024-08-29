<?php

namespace A17\Twill\Image\ViewModels;

use Spatie\ViewModels\ViewModel;
use A17\Twill\Image\Models\Image;
use A17\Twill\Image\Services\ImageStyles;
use Illuminate\Contracts\Support\Arrayable;

class ImageViewModel extends ViewModel implements Arrayable
{

    /**
     * @var string $alt Description of the image
     */
    protected $alt;

    /**
     * @var array $data Image source
     */
    protected $data;

    /**
     * @var int $height Height of the image
     */
    protected $height;

    /**
     * @var bool $imageSizer Should render the image sizer markup : false (default) or true
     */
    protected $imageSizer;

    /**
     * @var string $loading <img> loading attribute "lazy" (default) or "eager"
     */
    protected $loading;

    /**
     * @var bool $lqip Display a low-quality placeholder
     */
    protected $lqip;

    /**
     * @var array $sources LQIP image sources attributes
     */
    protected $lqipSources;

    /**
     * @var string $lqipSrc Default LQIP image source url
     */
    protected $lqipSrc;

    /**
     * @var string $sizes Sizes attributes
     */
    protected $sizes;

    /**
     * @var array $sources Image sources attributes
     */
    protected $sources;

    /**
     * @var string $src Default image source url
     */
    protected $src;

    /**
     * @var int $width Width of the image
     */
    protected $width;

    /**
     * @var int $imageClass Tailwind CSS class applied to the placeholder and main img tag
     */
    protected $imageClass;

    /**
     * @var int $imageStyles Styles applied to the placeholder and main img tag
     */
    protected $imageStyles;

    /**
     * @var ImageStyles
     */
    protected $styleService;

    /**
     * @var int $wrapperClass CSS class added to the wrapper element
     */
    protected $wrapperClass;

    /**
     * ImageViewModel format an Image instance attributes to be passed to the image wrapper view.
     *
     * @param Image|array $data Image source
     * @param array $overrides Overrides frontend options
     */
    public function __construct($data, $overrides = [])
    {
        if ($data instanceof Arrayable) {
            $this->data = $data->toArray();
        } else {
            $this->data = $data;
        }

        $this->setAttributes($overrides);
        $this->setImageAttributes();
        $this->setSourcesAttributes();
        $this->setLqipAttributes();

        $this->needPlaceholder = $this->lqip && $this->lqipSrc;
        $this->needPlaceholderOrSizer = $this->needPlaceholder || $this->imageSizer;

        $this->styleService = new ImageStyles();
        $this->styleService->setup(
            $this->imageStyles,
            $this->imageClass
        );
    }

    /**
     * Process arguments and apply default values.
     *
     * @param array $overrides
     * @return void
     */
    protected function setAttributes(array $overrides)
    {
        $this->lqip
            = $overrides['lqip']
            ?? config('twill-image.lqip')
            ?? true;

        $this->imageSizer
            = $overrides['imageSizer']
            ?? (
                (config('twill-image.image_sizer') === 'auto' && $this->lqip)
                || config('twill-image.image_sizer') === true
            );

        $this->loading = $overrides['loading'] ?? 'lazy';

        $this->sizes
            = $overrides['sizes']
            ?? $this->data['sizes']
            ?? null;

        $this->wrapperClass = $overrides['wrapperClass'] ?? $overrides['class'] ?? null;

        $this->width = $overrides['width'] ?? null;

        $this->height = $overrides['height'] ?? null;

        $this->imageStyles
            = $overrides['imageStyles']
            ?? [];

        $this->imageClass = $overrides['imageClass'] ?? $overrides['class'] ?? '';
    }

    /**
     * Set main image attributes
     *
     * @return void
     */
    protected function setImageAttributes()
    {
        $image = $this->data['image'];

        $this->src = $image['src'];
        $this->alt = $image['alt'];
        $this->width = $this->width ?? $image['width'];
        $this->height = $this->height ?? $image['height'];
    }

    /**
     * Construct the sources attributes from the image main and additional sources
     *
     * @return void
     */
    protected function setSourcesAttributes()
    {
        $sources = [];

        if (isset($this->data['sources'])) {
            foreach ($this->data['sources'] as $source) {
                $mediaQuery = $source['mediaQuery'];
                $image = $source['image'];

                if (config('twill-image.webp_support')) {
                    $sources[] = $this->buildSourceObject(
                        $image['srcSetWebp'],
                        $image['aspectRatio'],
                        $this->mimeType("webp"),
                        $mediaQuery
                    );
                }

                $sources[] = $this->buildSourceObject(
                    $image['srcSet'],
                    $image['aspectRatio'],
                    $this->mimeType($image['extension']),
                    $mediaQuery
                );
            }
        }

        $image = $this->data['image'];

        if (config('twill-image.webp_support')) {
            $sources[] = $this->buildSourceObject(
                $image['srcSetWebp'],
                $image['aspectRatio'],
                $this->mimeType("webp"),
            );
        }

        $sources[] = $this->buildSourceObject(
            $image['srcSet'],
            $image['aspectRatio'],
            $this->mimeType($image['extension'])
        );

        $this->sources = $sources;
    }

    protected function buildSourceObject($srcSet, $aspectRatio, $type = null, $mediaQuery = null)
    {
        return array_filter([
            'srcset' => $srcSet,
            'aspectRatio' => $aspectRatio * 100,
            'type' => $type,
            'mediaQuery' => $mediaQuery,
        ]);
    }

    protected function mimeType(string $extension): ?string
    {
        $ext = strtolower($extension);

        $types = [
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'webp' => 'image/webp',
        ];

        if (isset($types[$ext])) {
            return $types[$ext];
        }

        return null;
    }

    protected function wrapperClasses(): string
    {
        $classes = 'twill-image-wrapper';

        if (isset($this->wrapperClass)) {
            $classes = join(' ', [$classes, $this->wrapperClass]);
        }

        return join(' ', [$classes, $this->styleService->wrapper()['class']]);
    }

    /**
     * Set LQIP src and sources attributes
     *
     * @return void
     */
    protected function setLqipAttributes()
    {
        if (!$this->lqip) {
            $this->lqipSrc = null;
            return;
        }

        $image = $this->data['image'];

        $this->lqipSrc = $image['lqipBase64'];

        if (!isset($this->data['sources'])) {
            $this->lqipSources = null;
            return;
        }

        $sources = [];

        foreach ($this->data['sources'] as $source) {
            $mediaQuery = $source['mediaQuery'];
            $image = $source['image'];

            $sources[] = $this->buildSourceObject(
                sprintf('%s 1x', $image['lqipBase64']),
                $image['aspectRatio'],
                $this->mimeType("gif"),
                $mediaQuery
            );
        }

        $this->lqipSources = $sources;
    }

    public function toArray(): array
    {
        // CSS classes and styles
        $mainStyles = $this->styleService->main()['style'];
        $mainClasses = $this->styleService->main()['class'];
        $placeholderStyles = $this->styleService->placeholder()['style'];
        $placeholderClasses = $this->styleService->placeholder()['class'];
        $wrapperStyles = $this->styleService->wrapper()['style'];

        // needSizer or needPlaceholder : Append aspect ratio CSS rule to wrapper style
        $aspectRatio = $this->data['image']['aspectRatio'];
        $sep = '; ';
        $aspectRatioStyle = 'aspect-ratio: 100/' .number_format((float) ($aspectRatio) * 100, 2, '.', '');
        $wrapperStyles = !empty($wrapperStyles) ? $wrapperStyles . $sep . $aspectRatioStyle . $sep : $aspectRatioStyle . $sep;

        return array_filter([
            'alt' => $this->alt,
            'aspectRatio' => $aspectRatio,
            'height' => $this->height,
            'loading' => $this->loading,
            'mainStyle' => $mainStyles ?? null,
            'mainClasses' => $mainClasses ?? null,
            'mainSrc' => $this->src,
            'mainSources' => $this->sources,
            'needPlaceholder' => $this->needPlaceholder,
            'needSizer' => $this->imageSizer,
            'placeholderClasses' => $placeholderClasses ?? null,
            'placeholderSrc' => $this->lqipSrc,
            'placeholderSources' => $this->lqipSources,
            'placeholderStyle' => $placeholderStyles ?? null,
            'sizes' => $this->sizes,
            'width' => $this->width,
            'wrapperClasses' => $this->wrapperClasses(),
            'wrapperStyles' => $wrapperStyles ?? null,
        ]);
    }
}
