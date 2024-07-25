<?php

namespace A17\Twill\Image\Services;

use Illuminate\Support\Arr;
use A17\Twill\Image\Services\Interfaces\ImageColumns as ImageColumnsInterface;

class ImageColumns implements ImageColumnsInterface
{
    protected $config;

    protected static $configFile = 'frontend.config.json';

    protected $configBreakpoints = 'structure.breakpoints';

    protected $configMainColWidths = 'structure.container';

    protected $configInnerGutters = 'structure.gutters.inner';

    protected $configOuterGutters = 'structure.gutters.outer';

    protected $configColumns = 'structure.columns';

    protected $breakpoints;

    protected $mainColWidths;

    protected $innerGutters;

    protected $outerGutters;

    protected $columns;

    public function __construct()
    {
        $this->config = $this->getFEConfig();

        $breakpoints = Arr::get($this->config, $this->configBreakpoints);
        $firstBreakpoint = array_key_first($breakpoints);
        $this->breakpoints
            = intval($breakpoints[$firstBreakpoint]) === 0 ? $breakpoints : array_reverse($breakpoints);

        $this->mainColWidths = array_map(
            [$this, 'parseConfigToInt'],
            Arr::get($this->config, $this->configMainColWidths)
        );

        $this->innerGutters = array_map(
            [$this, 'parseConfigToInt'],
            Arr::get($this->config, $this->configInnerGutters)
        );

        $this->outerGutters = array_map(
            [$this, 'parseConfigToInt'],
            Arr::get($this->config, $this->configOuterGutters)
        );

        $this->columns = array_map(
            function ($column) {
                return intval($column);
            },
            Arr::get($this->config, $this->configColumns)
        );
    }

    public function sizes($sizes = []): string
    {
        $sizes_arr = [];

        foreach ($this->breakpoints as $name => $point) {
            $size = $this->calcSize($sizes[$name] ?? null, $name);

            if (isset($prevSize)) {
                // max-width mq
                $unit = $this->getUnit($point);
                $max = (intval($point) - 1) . $unit;
                $mqMax = "(max-width: $max)";

                $mq = isset($prevMqMin)
                    ? "$prevMqMin and $mqMax $prevSize"
                    : "$mqMax $prevSize";
                $sizes_arr[] = $mq;
            }

            if ($name !== array_key_first($this->breakpoints)) {
                // min-width mq
                $prevMqMin = "(min-width: $point)";
            }

            if ($name !== array_key_last($this->breakpoints)) {
                $prevSize = $size;
            } else {
                $sizes_arr[] = $size;
            }
        }

        return join(', ', $sizes_arr);
    }

    public function mediaQuery($args): string
    {
        $mediaQuery = [];

        foreach ($args as $name => $constrain) {
            if ($constrain === 'min' || $constrain === 'max') {
                $bp = $this->parseConfigToInt($this->breakpoints[$name]);
                $mq_num = ($constrain == 'max' ? $bp[0] - 1 : $bp[0]);
                $mediaQuery[] = "($constrain-width: $mq_num$bp[1])";
            }
        }

        return join(' and ', $mediaQuery);
    }

    public static function shouldInstantiateService(): bool
    {
        return file_exists(base_path(self::$configFile));
    }

    protected function getFEConfig()
    {
        $fe_config_json = file_get_contents(base_path(self::$configFile));

        return json_decode($fe_config_json, true);
    }

    protected function parseConfigToInt($str)
    {
        if ($str === 'auto') {
            $value = $str;
            $unit = '';
        } else {
            $value = intval($str);
            $unit = $this->getUnit($str);
        }

        return [$value, $unit];
    }

    protected function calcSize($size, $name)
    {
        $mainColWidth = $this->mainColWidths[$name];
        $columns = $this->columns[$name];
        $innerGutter = $this->innerGutters[$name];
        $outerGutter = $this->outerGutters[$name];

        if (isset($size)) {
            // if size is set in px/vw, use it
            if (strrpos($size, 'px') > 0 || strrpos($size, 'vw') > 0 || strrpos($size, 'em') > 0) {
                $sizeAttr = $size;
            } elseif ($mainColWidth[0] !== 'auto') {
                // Must follow container calculation https://github.com/area17/tailwind-plugins/blob/main/src/Container/index.js
                $container = $mainColWidth[0] - (2 * $outerGutter[0]);
                $width = round(((($container - ($columns - 1) * $innerGutter[0]) / $columns) * $size) + ($size - 1) * $innerGutter[0]);
                $sizeAttr = $width . $mainColWidth[1];
            } else {
                $gutterOffset = ((($columns - 1) * $innerGutter[0]) + (2 * $outerGutter[0])) . $innerGutter[1];
                $sizeAttr = "((100vw - $gutterOffset) / $columns) * $size";

                if ($size >= 1) {
                    $innerGutterOffset = (($size - 1) * $innerGutter[0]) . $innerGutter[1];
                    $sizeAttr = "($sizeAttr) + $innerGutterOffset";
                }

                $sizeAttr = 'calc('.$sizeAttr.')';
            }
        } else {
            if ($mainColWidth[0] !== 'auto') {
                $sizeAttr = $mainColWidth[0] . $mainColWidth[1];
            } else {
                $gutterOffset = (2 * $outerGutter[0]) . $outerGutter[1];
                $sizeAttr = "calc(100vw - $gutterOffset)";
            }
        }

        return $sizeAttr;
    }

    protected function getUnit($str)
    {
        return preg_replace('/[^a-z]+/', '', $str);
    }
}
