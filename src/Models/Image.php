<?php

namespace A17\Twill\Image\Models;

use A17\Twill\Models\Media;
use A17\Twill\Models\Model;
use A17\Twill\Image\Facades\TwillImage;
use A17\Twill\Image\Services\MediaSource;
use A17\Twill\Image\Services\ImageColumns;
use A17\Twill\Services\MediaLibrary\ImageService;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\Arrayable;
use A17\Twill\Image\Exceptions\ImageException;
use A17\Twill\Services\MediaLibrary\ImageServiceInterface;
use Illuminate\Foundation\Application;

class Image implements Arrayable
{
    /**
     * @var object|Model The model the media belongs to
     */
    protected $object;

    /**
     * @var string The media role
     */
    protected $role;

    /**
     * @var null|Media Media object
     */
    protected $media;

    /**
     * @var string The media crop
     */
    protected $crop;

    /**
     * @var int The media width
     */
    protected $width;

    /**
     * @var int The media height
     */
    protected $height;

    /**
     * @var array The media sources
     */
    protected $sources = [];

    /**
     * @var string Sizes attributes
     */
    protected $sizes;

    /**
     * @var int[] Widths list used to generate the srcset attribute
     */
    protected $srcSetWidths = [];

    /**
     * ImageService instance or class name
     *
     * @var string|ImageServiceInterface
     */
    protected $service;

    /**
     * @var ImageColumns|mixed
     */
    private $columnsService;

    /**
     * @var MediaSource|mixed
     */
    private $mediaSourceService;

    /**
     * @param object|Model $object
     * @param string $role
     * @param null|Media $media
     */
    public function __construct($object, string $role, ?Media $media = null)
    {
        $this->object = $object;

        $this->role = $role;

        $this->media = $media;

        $columnsServiceClass = config('twill-image.columns_class', ImageColumns::class);

        if ($columnsServiceClass::shouldInstantiateService()) {
            $this->columnsService = new $columnsServiceClass();
        }
    }

    /**
     * @return MediaSource
     * @throws ImageException
     */
    private function mediaSourceService(): MediaSource
    {
        return $this->mediaSourceService ?? $this->mediaSourceService = new MediaSource(
            $this->object,
            $this->role,
            $this->media,
            $this->service
        );
    }

    /**
     * Pick a preset from the configuration file or pass an array with the image configuration
     *
     * @param array|string $preset
     * @return $this
     * @throws ImageException
     */
    public function preset($preset): Image
    {
        if (is_array($preset)) {
            $this->applyPreset($preset);
        } elseif (config()->has("twill-image.presets.$preset")) {
            $this->applyPreset(config("twill-image.presets.$preset"));
        } else {
            throw new ImageException("Invalid preset value. Preset must be an array or a string corresponding to an image preset key in the configuration file.");
        }

        return $this;
    }

    public function columns($columns)
    {
        if (!isset($this->columnsService)) {
            return;
        }

        $this->sizes = $this->columnsService->sizes($columns);
    }

    protected function mediaQueryColumns($args)
    {
        if (!isset($this->columnsService)) {
            return null;
        }

        return $this->columnsService->mediaQuery($args);
    }

    /**
     * Set the list of srcset width to generate
     *
     * @param int[] $widths
     */
    public function srcSetWidths(array $widths)
    {
        $this->srcSetWidths = $widths;
    }

    /**
     * Set the crop of the media to use
     *
     * @param string $crop
     * @return $this
     */
    public function crop($crop): Image
    {
        $this->crop = $crop;

        return $this;
    }

    /**
     * Set a fixed with or max-width
     *
     * @param int $width
     * @return $this
     */
    public function width(int $width): Image
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Set a fixed height
     *
     * @param int $height
     * @return $this
     */
    public function height(int $height): Image
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Set the image sizes attributes
     *
     * @param string $sizes
     * @return $this
     */
    public function sizes(string $sizes): Image
    {
        $this->sizes = $sizes;

        return $this;
    }

    /**
     * Set alternative sources for the media.
     *
     * @param array $sources
     * @return $this
     */
    public function sources(array $sources = []): Image
    {
        $this->sources = $sources;

        return $this;
    }

    /**
     * Set the ImageService to use instead of the one provided
     * by the service container
     *
     * @param string|ImageServiceInterface $service
     * @return $this
     */
    public function service($service): Image
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Set alternative sources for the media.
     *
     * @throws ImageException
     */
    public function generateSources(): array
    {
        $sources = [];

        foreach ($this->sources as $source) {
            if (!isset($source['media_query']) && !isset($source['mediaQuery']) && !isset($source['columns'])) {
                throw new ImageException("Media query is mandatory in sources.");
            }

            if (!isset($source['crop'])) {
                throw new ImageException("Crop name is mandatory in sources.");
            }

            $sources[] = [
                "mediaQuery" => isset($source['columns'])
                    ? $this->mediaQueryColumns($source['columns'])
                    : $source['media_query'] ?? $source['mediaQuery'],
                "image" => $this->mediaSourceService()->generate(
                    $source['crop'],
                    $source['width'] ?? null,
                    $source['height'] ?? null,
                    $source['srcSetWidths'] ?? []
                )->toArray()
            ];
        }

        return $sources;
    }

    /**
     * Call the Facade render method to output the view
     *
     * @return void
     */
    public function render($overrides = [])
    {
        /* @phpstan-ignore-next-line */
        return TwillImage::render($this, $overrides);
    }

    /**
     * @throws ImageException
     */
    public function toArray(): array
    {
        $arr = [
            "image" => $this->mediaSourceService()->generate(
                $this->crop,
                $this->width,
                $this->height,
                $this->srcSetWidths
            )->toArray(),
            "sizes" => $this->sizes,
            "sources" => $this->generateSources(),
        ];

        return array_filter($arr);
    }

    protected function applyPreset($preset)
    {
        if (!isset($preset)) {
            return;
        }

        if (isset($preset['crop'])) {
            $this->crop($preset['crop']);
        }

        if (isset($preset['width'])) {
            $this->width($preset['width']);
        }

        if (isset($preset['height'])) {
            $this->height($preset['height']);
        }

        if (isset($preset['sizes'])) {
            $this->sizes($preset['sizes']);
        }

        if (isset($preset['columns'])) {
            $this->columns($preset['columns']);
        }

        if (isset($preset['sources'])) {
            $this->sources($preset['sources']);
        }

        if (isset($preset['srcSetWidths'])) {
            $this->srcSetWidths($preset['srcSetWidths']);
        }

        if (isset($preset['service'])) {
            $this->service($preset['service']);
        }
    }
}
