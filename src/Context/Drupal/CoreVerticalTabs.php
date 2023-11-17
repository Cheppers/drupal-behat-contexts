<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;

class CoreVerticalTabs extends Base
{
    protected function initFinders()
    {
        parent::initFinders();
        // @codingStandardsIgnoreStart
        $this->finders += [
            'drupal.core.vertical_tabs.wrapper_by_label' => [
                'selector' => 'xpath',
                'locator' => sprintf(
                    '//div[%s and %s]',
                    'contains(@class, "form-type-vertical-tabs")',
                    'descendant::label[normalize-space(text()) = "{{ label }}"]'
                ),
            ],
            'drupal.core.vertical_tabs.tab_by_label' => [
                'selector' => 'xpath',
                'locator' => '//li/a/*[@class = "vertical-tabs__menu-item-title" and normalize-space(text()) = "{{ label }}"]',
            ],
            'drupal.core.vertical_tabs.tab_labels' => [
                'selector' => 'xpath',
                'locator' => '//li/a/*[@class = "vertical-tabs__menu-item-title"]',
            ],
        ];
        // @codingStandardsIgnoreEnd
        return $this;
    }

    /**
     * @When /^I activate the "(?P<tabLabel>[^"]+)" vertical tab in the "(?P<groupLabel>[^"]+)"$/
     */
    public function doVerticalTabsActivateTab(string $tabLabel, string $wrapperLabel)
    {
        $wrapperElement = $this->getVerticalTabsWrapperByLabel($wrapperLabel, true);

        $tabFinder = $this->getFinder(
            'drupal.core.vertical_tabs.tab_by_label',
            [
                '{{ label }}' => $this->escapeXpathValue($tabLabel),
            ]
        );

        $tabElement = $wrapperElement->find($tabFinder['selector'], $tabFinder['locator']);

        if (!$tabElement) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $tabFinder['selector'],
                $tabFinder['locator']
            );
        }

        $tabElement->click();
    }

    /**
     * @Then the :label vertical tabs contains the following tabs:
     */
    public function assertSameVerticalTabsTabLabels(string $label, TableNode $tabs)
    {
        $wrapperElement = $this->getVerticalTabsWrapperByLabel($label, true);

        Assert::assertSame(
            $tabs->getColumn(0),
            $this->getVerticalTabsTabLabels($wrapperElement)
        );
    }

    protected function getVerticalTabsWrapperByLabel(string $label, bool $required = false): ?NodeElement
    {
        $finder = $this->getFinder(
            'drupal.core.vertical_tabs.wrapper_by_label',
            [
                '{{ label }}' => $this->escapeXpathValue($label),
            ]
        );

        $wrapper = $this
            ->getSession()
            ->getPage()
            ->find($finder['selector'], $finder['locator']);

        if ($required && !$wrapper) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $finder['selector'],
                $finder['locator']
            );
        }

        return $wrapper;
    }

    /**
     * @return string[]
     */
    protected function getVerticalTabsTabLabels(NodeElement $wrapperElement): array
    {
        $finder = $this->getFinder('drupal.core.vertical_tabs.tab_labels');

        $tabLabels = [];
        /** @var NodeElement[] $tabLabelElements */
        $tabLabelElements = $wrapperElement->findAll($finder['selector'], $finder['locator']);
        foreach ($tabLabelElements as $labelElement) {
            $tabLabels[] = $labelElement->getText();
        }

        return $tabLabels;
    }
}
