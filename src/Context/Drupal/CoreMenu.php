<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;

class CoreMenu extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function initFinders()
    {
        parent::initFinders();

        $wrapperLocator = '//nav[@role="navigation"]/*[text() = "{{ menuLabel }}"]/parent::*';

        $this->finders += [
            'drupal.core.menu.wrapper' => [
                'selector' => 'xpath',
                'locator' => $wrapperLocator,
            ],
            'drupal.core.menu.links' => [
                'selector' => 'xpath',
                'locator' => "$wrapperLocator/div[@class=\"content\"]//ul//a",
            ],
        ];

        return $this;
    }

    /**
     * @Then /^I should see the following links in the "(?P<menuLabel>[^"]*)" menu:$/
     */
    public function assertMenuLinksSameTable(string $menuLabel, TableNode $table)
    {
        $expectedLinkLabels = $table->getColumn(0);
        $actualLinkElements = $this->getMenuLinks($menuLabel);

        Assert::assertSameSize(
            $expectedLinkLabels,
            $actualLinkElements,
            sprintf(
                'Expected number of links is %d. Actual: %d',
                count($expectedLinkLabels),
                count($actualLinkElements)
            )
        );

        foreach ($actualLinkElements as $delta => $actualLinkElement) {
            Assert::assertSame(
                $expectedLinkLabels[$delta],
                $actualLinkElement->getText(),
                sprintf(
                    'Expected link title is "%s". Actual: "%s"',
                    $expectedLinkLabels[$delta],
                    $actualLinkElement->getText()
                )
            );
        }
    }

    /**
     * @When /^I click "(?P<linkLocator>[^"]+)" in the "(?P<menuLabel>[^"]+)" menu$/
     */
    public function doClickOnMenuItem(string $menuLabel, string $linkLocator)
    {
        $this
            ->getMenuElement($menuLabel)
            ->clickLink($linkLocator);
    }

    protected function findMenuElement(string $menuLabel): ?NodeElement
    {
        $menuWrapperFinder = $this->getFinder(
            'drupal.core.menu.wrapper',
            [
                '{{ menuLabel }}' => $this->escapeXpathValue($menuLabel),
            ]
        );

        return $this
            ->getSession()
            ->getPage()
            ->find($menuWrapperFinder['selector'], $menuWrapperFinder['locator']);
    }

    protected function getMenuElement(string $menuLabel): NodeElement
    {
        $menuElement = $this->findMenuElement($menuLabel);
        Assert::assertNotEmpty($menuElement, sprintf('Menu wrapper by menu label: "%s"', $menuLabel));

        return $menuElement;
    }

    /**
     * @return NodeElement[]
     */
    protected function getMenuLinks(string $menuLabel): array
    {
        $menuLinksFinder = $this->getFinder(
            'drupal.core.menu.links',
            [
                '{{ menuLabel }}' => $this->escapeXpathValue($menuLabel),
            ]
        );

        return $this
            ->getSession()
            ->getPage()
            ->findAll($menuLinksFinder['selector'], $menuLinksFinder['locator']);
    }
}
