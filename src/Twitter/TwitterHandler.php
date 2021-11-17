<?php

namespace DynamicScreen\Twitter;

use App\Domain\Module\Model\Module;
use DynamicScreen\SdkPhp\Handlers\SlideHandler;
use DynamicScreen\SdkPhp\Interfaces\ISlide;

class TwitterHandler extends SlideHandler
{
    public function __construct(Module $module)
    {
        parent::__construct($module);
    }

    public function fetch(ISlide $slide): void
    {
        $default = $this->getDefaultOptions();

        $this->addSlide([]);
    }

    public function getDefaultOptions(): array
    {
        return [];
    }
}
