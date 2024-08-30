<?php

namespace A17\Twill\Image;

use A17\Twill\Models\Block;
use A17\Twill\Models\Media;
use A17\Twill\Models\Model;
use A17\Twill\Image\Models\Image as TwillImageModel;
use A17\Twill\Image\ViewModels\ImageViewModel;
use Illuminate\Contracts\View\View;

class TwillImage
{
    /**
     * @param object|Model|Block $object
     * @param string $role
     * @param Media|null $media
     * @return TwillImageModel
     */
    public function make($object, string $role, Media $media = null): TwillImageModel
    {
        return new TwillImageModel($object, $role, $media);
    }

    /**
     * @param TwillImageModel|array $data
     * @param array $overrides
     * @return View
     */
    public function render($data, array $overrides = []): View
    {
        $viewModel = new ImageViewModel($data, $overrides);

        return view('twill-image::wrapper', $viewModel);
    }
}
