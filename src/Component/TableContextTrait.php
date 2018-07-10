<?php

namespace Cheppers\DrupalExtension\Component;

use Behat\Mink\Exception\ElementNotFoundException;

trait TableContextTrait
{
    protected function getColumnIndex(string $columnLabel): int
    {
        $selector = 'xpath';
        $locator = sprintf(
            '//table/thead/tr/th[starts-with(., "%s")]',
            $columnLabel
        );

        /** @var \Behat\Mink\Element\NodeElement $cellElement */
        $cellElement = $this
            ->getSession()
            ->getPage()
            ->find($selector, $locator);

        if (!$cellElement) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $selector,
                $locator
            );
        }

        $precedingElements = $cellElement->findAll($selector, 'preceding::th');

        return count($precedingElements) + 1;
    }
}
