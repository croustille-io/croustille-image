<?php

namespace A17\Twill\Image\Services;

use A17\Twill\Models\Block;
use A17\Twill\Models\Media;
use A17\Twill\Models\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Support\Arrayable;
use A17\Twill\Image\Exceptions\ImageException;
use A17\Twill\Services\MediaLibrary\ImageServiceInterface;

class MediaSource implements Arrayable
{
    public const AUTO_WIDTHS_RATIO = 2.5;

    public const DEFAULT_WIDTH = 1000;

    public const FORMAT_WEBP = 'webp';

    public const CROP_KEYS = ['crop_x', 'crop_y', 'crop_w', 'crop_h'];

    protected $model;

    protected $role;

    protected $media;

    protected $crop;

    protected $width;

    protected $srcSetWidths;

    protected $height;

    protected $imageArray;

    /**
     * Create an instance of the service
     *
     * @param Model|Block|object $object
     * @param string $role
     * @param Media|object|null $media
     * @param null $service
     * @throws ImageException
     */
    public function __construct($object, string $role, $media = null, $service = null)
    {
        $this->setModel($object);

        $this->role = $role;
        $this->media = $media;
        $this->service = $this->setService($service);
    }

    /**
     * @throws ImageException
     */
    public function generate($crop = null, $width = null, $height = null, $srcSetWidths = []): MediaSource
    {
        $this->setCrop($crop);
        $this->setImageArray();
        $this->setWidth($width);
        $this->setHeight($height);

        $this->srcSetWidths = $srcSetWidths;

        return $this;
    }

    /**
     * @throws ImageException
     */
    protected function setModel($object)
    {
        if (!classHasTrait($object, 'A17\Twill\Models\Behaviors\HasMedias')) {
            throw new ImageException('Object must use HasMedias trait', 1);
        }

        $this->model = $object;
    }

    protected function setService($service)
    {
        if (isset($service) && is_string($service) && class_exists($service)) {
            return App::make($service);
        } elseif (isset($service) && is_object($service)) {
            return $service;
        }
        return app('imageService');
    }

    /**
     * Set model crop
     *
     * @param null|string $crop
     * @return void
     * @throws ImageException
     */
    protected function setCrop(?string $crop)
    {
        if (isset($crop)) {
            $this->crop = $crop;
            return;
        }

        $crops = $this->getAllCrops($this->model, $this->role);


        if ($index = array_search('default', $crops)) {
            $this->crop = $crops[$index];
            return;
        }

        if (count($crops) > 1) {
            throw new ImageException(
                "Image role has more than one crop, please add a crop key to the arguments",
                1,
            );
        }

        $this->crop = $crops[0];
    }

    protected function setWidth($width)
    {
        if (isset($width)) {
            $this->width = $width;
        } else {
            $this->width = min($this->imageArray['width'], self::DEFAULT_WIDTH);
        }
    }

    protected function setHeight($height)
    {
        $this->height = $height ?? null;
    }

    /**
     * @throws ImageException
     */
    protected function setImageArray()
    {
        $width = $this->media()->pivot->crop_w ?? $this->media()->width ?? null;
        $height = $this->media()->pivot->crop_h ?? $this->media()->height ?? null;

        if (isset($width) && isset($height)) {
            $this->imageArray = [
                'width' => $width,
                'height' => $height,
            ];
        }

        if (empty($this->imageArray)) {
            throw new ImageException(
                "No media was found for role '{$this->role}' and crop '{$this->crop}'",
                1,
            );
        }
    }

    protected function params($width, $height = null, $format = null)
    {
        $args = [
            'w' => $width,
        ];

        if (isset($height)) {
            $args['h'] = $height;
            $args['fit'] = 'crop';
        }

        if (isset($format)) {
            $args['fm'] = $format;
        }

        return $args;
    }

    protected function calcHeightFromWidth($width)
    {
        $height
            = isset($this->height)
            ? $width * $this->height / $this->width
            : $width * $this->imageArray['height'] / $this->imageArray['width'];

        return (int) $height;
    }

    protected function image($media, $params)
    {
        return $this->service->getUrlWithCrop(
            $media->uuid,
            Arr::only($media->pivot->toArray(), self::CROP_KEYS),
            $params
        );
    }

    public function src()
    {
        return $this->getSrc($this->width, $this->height);
    }

    public function srcWebp()
    {
        return $this->getSrc($this->width, $this->height, self::FORMAT_WEBP);
    }

    protected function getSrc($width, $height, $format = null)
    {
        $params = $this->params($width, $height, $format);

        return $this->image($this->media(), $params);
    }

    public function lqipBase64()
    {
        return $this->media()->pivot->lqip_data ??
            $this->service->getTransparentFallbackUrl();
    }

    public function srcSet(): string
    {
        return $this->getSrcset();
    }

    public function srcSetWebp(): string
    {
        return $this->getSrcSet(self::FORMAT_WEBP);
    }

    protected function getSrcSet($format = null): string
    {
        $range = !empty($this->srcSetWidths) ? collect($this->srcSetWidths) : collect($this->widthRange());

        return $range
            ->map(function ($width) use ($format) {
                return sprintf(
                    "%s %sw",
                    $this->getSrc(
                        $width,
                        isset($this->height) ? $this->calcHeightFromWidth($width) : null,
                        $format
                    ),
                    $width
                );
            })
            ->join(', ');
    }

    protected function widthRange(): array
    {
        $baseWidth = $this->width;

        // weird science 🥸
        $range = array_merge(
            range(min(250, $baseWidth), 1250, 250),
            range(1500, 10000, 500),
        );

        return array_filter($range, function ($width) use ($baseWidth) {
            return $width <= $baseWidth * self::AUTO_WIDTHS_RATIO;
        });
    }

    public function width()
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height ?? $this->calcHeightFromWidth($this->width);
    }

    public function aspectRatio(): string
    {
        $width = $this->width;

        $height
            = $this->height
            ?? $width
                * $this->imageArray['height']
                / $this->imageArray['width'];

        return (float) ($height / $width);
    }

    /**
     * Provide the text description of the image
     *
     * @return string
     */
    public function alt(): string
    {
        return $this->model->imageAltText($this->role, $this->media);
    }

    public function caption(): string
    {
        return $this->model->imageCaption($this->role, $this->media);
    }

    public function extension(): string
    {
        return pathinfo($this->media()->filename, PATHINFO_EXTENSION);
    }

    public function ratio(): ?string
    {
        return $this->media()->pivot->ratio;
    }

    protected function media()
    {
        return $this->media ?? $this->model->imageObject($this->role, $this->crop);
    }

    public function toArray(): array
    {
        return array_merge([
            "alt" => $this->alt(),
            "aspectRatio" => $this->aspectRatio(),
            "caption" => $this->caption(),
            "crop" => $this->crop,
            "extension" => $this->extension(),
            "height" => $this->height(),
            "lqipBase64" => $this->lqipBase64(),
            "ratio" => $this->ratio(),
            "src" => $this->src(),
            "srcSet" => $this->srcSet(),
            "width" => $this->width(),
        ], (config('twill-image.webp_support') ? [
            "srcWebp" => $this->srcWebp(),
            "srcSetWebp" => $this->srcSetWebp(),
        ] : []));
    }

    /**
     * Build crops list
     *
     * @param $model
     * @param $role
     * @return array
     * @throws ImageException
     */
    protected function getAllCrops($model, $role): array
    {
        $crops = array_values(
            $model->medias
                ->filter(function ($media) use ($role) {
                    return $media->pivot->role === $role;
                })
                ->map(function ($media) {
                    return $media->pivot->crop;
                })
                ->toArray(),
        );

        if (empty($crops)) {
            throw new ImageException("Can't find any crops for role '$this->role'");
        }

        return $crops;
    }
}
