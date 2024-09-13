<?php

namespace A17\Twill\Image\Http\Controllers;

use A17\Twill\Image\Services\Glide;
use Illuminate\Foundation\Application;

class GlideController
{
    public function __invoke(string $path, Application $app)
    {
        return $app->make(Glide::class)->render($path);
    }
}
