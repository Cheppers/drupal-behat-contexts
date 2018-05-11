<?php

namespace Cheppers\DrupalExtension\Component;

trait ScrollToElementContextTrait
{
    protected function scrollToElementByCssLocator(string $locator)
    {
        $locatorSafe = addslashes($locator);
        $js = <<< JS
var scrollDownValue = window.innerHeight / 2;
document
  .querySelector("$locatorSafe")
  .scrollIntoView();
window
  .scrollBy(0, -scrollDownValue);
JS;

        /** @var \Behat\Mink\Session $session */
        $session = $this->getSession();
        $session->executeScript($js);
    }
}
