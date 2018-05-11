<?php

namespace Cheppers\DrupalExtension\Component\Drupal;

use Behat\Mink\Element\NodeElement;

trait CoreEntityFormContextTrait
{
    protected function findEntityFormFieldWidgetByFieldName(string $fieldName): ?NodeElement
    {
        $selector = 'xpath';
        $locator = sprintf('//*[@data-drupal-selector="%s"]', "edit-$fieldName-wrapper");

        return $this
            ->getSession()
            ->getPage()
            ->find($selector, $locator);
    }
}
