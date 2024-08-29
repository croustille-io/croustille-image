<?php

namespace A17\Twill\Image\Models;

use A17\Twill\Models\Model;
use A17\Twill\Image\Models\Image as TwillImageModel;
use A17\Twill\Models\Behaviors\HasMedias;
use A17\Twill\Image\Exceptions\ImageException;

class StaticImage extends Model
{
    use HasMedias;

    protected $fillable = [];

    public $mediasParams = [];

    public static $role = 'static-image';

    public static function makeFromSrc($args): TwillImageModel
    {
        $model = self::make();

        $role = self::$role;

        $preset = self::getPresetObject($args['preset'] ?? null);

        $files = $args['files'] ?? $args['file'] ?? null;

        $ratios = $args['ratios'] ?? $args['ratio'] ?? null;

        $crop = $preset['crop'] ?? 'default';

        $model->makeMedia([
            'src' => self::getFile($files, $crop),
            'ratio' => self::getRatio($ratios, $crop),
            'role' => $role,
            'crop' => $crop,
            'alt' => $args['alt'] ?? null,
        ]);

        if (!empty($preset['sources']) && $sources = $preset['sources']) {
            foreach ($sources as $source) {
                $model->makeMedia([
                    'src' => self::getFile($files, $source['crop']),
                    'ratio' => self::getRatio($ratios, $source['crop']),
                    'role' => $role,
                    'crop' => $source['crop'],
                ]);
            }
        }

        $image = new Image($model, $role);

        $image->service(\A17\Twill\Image\Services\Glide::class);

        if (isset($args['preset'])) {
            $image->preset($args['preset']);
        }

        return $image;
    }

    private static function getPresetObject($preset)
    {
        if (is_array($preset)) {
            return $preset;
        } elseif (config()->has("twill-image.presets.$preset")) {
            return config("twill-image.presets.$preset");
        } else {
            return [];
        }
    }

    private static function getFile($files, $crop)
    {
        if (is_array($files) && isset($files[$crop])) {
            return $files[$crop];
        } elseif (is_string($files)) {
            return $files;
        } else {
            throw new ImageException("Invalid file(s) value in arguments.");
        }
    }

    private static function getRatio($ratios, $crop)
    {
        if (is_array($ratios) && isset($ratios[$crop])) {
            return $ratios[$crop];
        } elseif (is_numeric($ratios)) {
            return $ratios;
        } else {
            return null;
        }
    }

    public function makeMedia($args)
    {
        $src = $args['src'];

        $role = $args['role'];

        $crop = $args['crop'];

        $uuid = parse_url($src, PHP_URL_PATH);

        $size = $this->getInputSize($uuid);

        $width = $size[0];

        $height = $size[1];

        $ratio = $args['ratio'] ?? null;

        $cropData = $this->calcCrop($width, $height, $ratio);

        $media = \A17\Twill\Models\Media::make([
            'uuid' => $uuid,
            'filename' => basename($uuid),
            'width' => $width,
            'height' => $height,
            'alt_text' => $args['alt'] ?? null,
        ]);

        $data = [
            'role' => $role,
            'crop' => $crop,
        ] + $cropData;

        $pivot = $media->newPivot(
            $this,
            $data,
            config('twill.mediables_table', 'twill_mediables'),
            true,
        );

        $media->setRelation('pivot', $pivot);

        $this->medias->add($media);
    }

    private function getInputSize($uuid)
    {
        $file_path = implode('/', [
            rtrim(config('twill-image.static_local_path'), '/'),
            ltrim($uuid, '/'),
        ]);

        $size = getimagesize($file_path);

        return [$size[0], $size[1]];
    }

    private function calcCrop($inputWidth, $inputHeight, $outputRatio = null)
    {
        $inputImageAspectRatio =  $inputWidth / $inputHeight;
        $outputImageAspectRatio = isset($outputRatio) ? $outputRatio : $inputImageAspectRatio;

        $outputWidth = $inputWidth;
        $outputHeight = $inputHeight;

        if ($inputImageAspectRatio > $outputImageAspectRatio) {
            $outputWidth = $inputHeight * $outputImageAspectRatio;
        } elseif ($inputImageAspectRatio < $outputImageAspectRatio) {
            $outputHeight = $inputWidth / $outputImageAspectRatio;
        }

        return [
            'crop_x' => $outputWidth < $inputWidth ? ($inputWidth - $outputWidth) / 2 : 0,
            'crop_y' => $outputHeight < $inputHeight ? ($inputHeight - $outputHeight) / 2 : 0,
            'crop_w' => $outputWidth,
            'crop_h' => $outputHeight,
            'ratio' => $outputImageAspectRatio,
        ];
    }
}
