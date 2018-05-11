<?php

namespace Cheppers\DrupalExtension\Component;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Driver\Exception\UnsupportedDriverActionException;

trait ClickElementContextTrait
{
    protected function clickElementByCssLocator(string $locator)
    {
        /** @var \Behat\Mink\Session $session */
        $session = $this->getSession();
        $element = $session
            ->getPage()
            ->find('css', $locator);

        if (!$element) {
            throw new ElementNotFoundException($session, 'element', 'css', $locator);
        }

        try {
            $this->scrollToElementByCssLocator($locator);
        } catch (UnsupportedDriverActionException $e) {
            // Don't worry about it.
        }

        $element->click();
    }
}
