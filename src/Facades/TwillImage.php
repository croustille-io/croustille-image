<?php

namespace A17\Twill\Image\Facades;

use Illuminate\Support\Facades\Facade;

class TwillImage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'twill.image';
    }
}
