<?php

namespace A17\Twill\Image\Services;

class ImageStyles
{
    /**
     * @var string $mode 'inline-styles'|'classes'|'both'
     */
    protected $mode = 'inline-styles';

    /**
     * @var array $inlineStyles Inline styles to be applied to elements
     */
    protected $inlineStyles = [];

    /**
     * @var array $classes Classes to be applied to elements
     */
    protected $classes = [];

    /**
     * Set up the service to generate view inline styles for the wrapper, main image and placeholder elements
     *
     * @param array $styles
     * @param string $classes
     * @return void
     */
    public function setup(array $styles = [], string $classes = '')
    {
        $this->mode = config('twill-image.mode');
        $this->setBaseStyling($styles, $classes);
    }

    /**
     * Return inline styles and classes for the wrapper element
     *
     * @return array
     */
    public function wrapper(): array
    {
        $styles = $this->inlineStyles['wrapper'] ?? [];
        $classes = $this->classes['wrapper'] ?? [];

        return $this->implode($styles, $classes);
    }

    /**
     * Return inline styles and classes for the placeholder element
     *
     * @return array
     */
    public function placeholder(): array
    {
        $styles = array_merge(
            $this->inlineStyles['main'] ?? [],
            $this->inlineStyles['placeholder'] ?? []
        );
        $classes = array_merge(
            $this->classes['main'] ?? [],
            $this->classes['placeholder'] ?? []
        );

        return $this->implode($styles, $classes);
    }

    /**
     * Return inline styles and classes for the main image element
     *
     * @return array
     */
    public function main()
    {
        return $this->implode(
            $this->inlineStyles['main'] ?? [],
            $this->classes['main'] ?? []
        );
    }

    protected function setBaseStyling($style, $class)
    {
        $this->inlineStyles['main'] = config('twill-image.inline_styles.main', []);
        $this->inlineStyles['wrapper'] = config('twill-image.inline_styles.wrapper', []);
        $this->inlineStyles['placeholder'] = config('twill-image.inline_styles.placeholder', []);
        $this->classes['main'] = config('twill-image.classes.main', []);
        $this->classes['wrapper'] = config('twill-image.classes.wrapper', []);
        $this->classes['placeholder'] = config('twill-image.classes.placeholder', []);

        switch ($this->mode) {
            case 'inline-styles':
                $this->classes['main'] = [];
                $this->classes['wrapper'] = [];
                $this->classes['placeholder'] = [];
                break;
            case 'classes':
                $this->inlineStyles['main'] = [];
                $this->inlineStyles['wrapper'] = [];
                $this->inlineStyles['placeholder'] = [];
                break;
            case 'both':
            default:
        }

        $this->inlineStyles['main'] = array_merge($this->inlineStyles['main'], $style);
        $this->classes['main'] = array_merge($this->classes['main'], [$class]);
    }

    protected function implode($styles = [], $classes = [])
    {
        return [
            'style' => $this->implodeStyles($styles),
            'class' => $this->implodeClasses($classes),
        ];
    }

    protected function implodeStyles($style)
    {
        return implode(
            ';',
            array_map(
                function ($property, $value) {
                    return "$property:$value";
                },
                array_keys($style),
                $style,
            ),
        );
    }

    protected function implodeClasses($classes)
    {
        return implode(' ', $classes);
    }
}
