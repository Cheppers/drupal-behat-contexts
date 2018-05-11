<?php

namespace Cheppers\DrupalExtension\Component;

use PHPUnit_Framework_Assert as Assert;
use WebDriver\Exception\ElementIsNotSelectable;

trait XpathContextTrait
{
    protected function assertNumberByXpathSelector(float $number, string $xpathSelector)
    {
        $element = $this
            ->getSession()
            ->getPage()
            ->find('xpath', $xpathSelector);

        if (!$element) {
            throw new ElementIsNotSelectable("Element can not be selected: $xpathSelector");
        }

        Assert::assertSame($number, $this->numerify($element->getText()));
    }

    protected function numerify(string $string): float
    {
        return (float) preg_replace('/([^\d]+)?/', '', $string);
    }
}
