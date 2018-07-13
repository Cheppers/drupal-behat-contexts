<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;

class Core extends Base
{
    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.core.page_title' => [
                'selector' => 'css',
                'locator' => '.block-page-title-block h1.page-title',
            ],
        ];
    }

    /**
     * @Then the page title is :title
     */
    public function assertPageTitleIs(string $title)
    {
        $finder = $this->getFinder('drupal.core.page_title');

        $titleElement = $this
            ->getSession()
            ->getPage()
            ->find($finder['selector'], $finder['locator']);

        if (!$titleElement) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $finder['selector'],
                $finder['locator']
            );
        }

        Assert::assertSame($title, $titleElement->getText());
    }

    /**
     * @Then the page is themed by the :themeName theme
     */
    public function assertPageIsThemedBy(string $themeName)
    {
        Assert::assertSame($themeName, $this->getThemeDetector()->getCurrentThemeName($this->getSession()));
    }
}
