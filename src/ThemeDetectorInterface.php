<?php

namespace Cheppers\DrupalExtension;

use Behat\Mink\Session;

interface ThemeDetectorInterface
{
    public function getCurrentThemeName(Session $session): string;
}
