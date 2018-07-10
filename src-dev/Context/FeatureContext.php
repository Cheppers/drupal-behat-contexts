<?php

namespace Cheppers\DrupalExtensionDev\Context;

use Cheppers\DrupalExtension\Context\Base;

class FeatureContext extends Base
{
    /**
     * @BeforeScenario @javascript
     */
    public function beforeScenarioJavascriptWindowSize()
    {
        $this
            ->getSession()
            ->resizeWindow(1440, 1080);
    }
}
