<?php

declare(strict_types = 1);

namespace Cheppers\DrupalExtension\Context\Chrome;

use Cheppers\DrupalExtension\Context\Base;

class Chrome extends Base
{
    /**
     * @Given /^the window size is (?P<width>\d+)(x|×)(?P<height>\d+)$/
     * @When /^I change the window size to (?P<width>\d+)(x|×)(?P<height>\d+)$/
     */
    public function doChangeWindowSizeTo(string $width, string $height)
    {
        $this
            ->getSession()
            ->resizeWindow($width, $height);
    }
}
