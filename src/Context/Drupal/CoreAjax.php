<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Component\Drupal\CoreAjaxContextTrait;
use Cheppers\DrupalExtension\Context\Base;

class CoreAjax extends Base
{
    use CoreAjaxContextTrait;

    /**
     * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
     *
     * @Given /^I wait (?P<seconds>\d+(\.\d+)?) second for AJAX to finish$/
     * @Given /^I wait (?P<seconds>\d+(\.\d+)?) seconds for AJAX to finish$/
     */
    public function doWaitForAjaxToFinish(float $seconds)
    {
        $this->waitForAjaxToFinish($seconds);
    }
}
