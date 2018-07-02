<?php

namespace Cheppers\DrupalExtension\Component\Drupal;

use PHPUnit_Framework_Assert as Assert;

trait CoreThemeDetectorContextTrait
{
    /**
     * @todo The current detection method is not bulletproof.
     */
    protected function getCurrentThemeName(): string
    {
        $themeName = $this->getCurrentThemeNameByAjaxPageState();
        if ($themeName) {
            return $themeName;
        }

        $themeName = $this->getCurrentThemeNameByFavicon();
        if ($themeName) {
            return $themeName;
        }

        Assert::assertNotEmpty($themeName, 'The current theme cannot be detected');

        return '';
    }

    protected function getCurrentThemeNameByFavicon(): string
    {
        $xpathQuery = '/head/link[@rel="shortcut icon"][@href]';

        /** @var \Behat\Mink\Element\NodeElement $linkElement */
        $linkElement = $this
            ->getSession()
            ->getPage()
            ->find('xpath', $xpathQuery);

        Assert::assertNotEmpty($linkElement, 'The current theme cannot be detected');

        $href = $linkElement->getAttribute('href');
        $hrefParts = explode('/', $href);
        array_pop($hrefParts);

        return (string) end($hrefParts);
    }

    protected function getCurrentThemeNameByAjaxPageState(): string
    {
        $js = <<< JS
return drupalSettings.ajaxPageState.theme;
JS;

        return (string) $this->getSession()->evaluateScript($js);
    }
}
