<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;

class CoreTabs extends Base
{
    use CoreContentEntityContextTrait;

    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.core.tabs.primary_tabs_wrapper' => [
                'selector' => 'css',
                'locator' => '.tabs.primary',
            ],
            'drupal.core.tabs.primary_tabs_links' => [
                'selector' => 'css',
                'locator' => 'a',
            ],
        ];

        return $this;
    }

    /**
     * @Then /^I should see the following primary tabs:$/
     */
    public function assertPrimaryTabsTable(TableNode $table)
    {
        $expected = $table->getColumn(0);
        $actual = $this->getPrimaryTabsLinkLabels();

        Assert::assertSame($expected, $actual);
    }

    /**
     * @When /^I click "(?P<linkText>[^"]+)" primary tab$/
     */
    public function doClickPrimaryTab(string $linkText)
    {
        $this
            ->getPrimaryTabsElement(true)
            ->clickLink($linkText);
    }

    protected function getPrimaryTabsElement(bool $required = false): ?NodeElement
    {
        $primaryTabsWrapperFinder = $this->getFinder('drupal.core.tabs.primary_tabs_wrapper');

        $primaryTabsElement = $this
            ->getSession()
            ->getPage()
            ->find($primaryTabsWrapperFinder['selector'], $primaryTabsWrapperFinder['locator']);

        if (!$required && !$primaryTabsElement) {
            throw  new ElementNotFoundException(
                $this->getSession()->getDriver(),
                'other',
                $primaryTabsWrapperFinder['selector'],
                $primaryTabsWrapperFinder['locator']
            );
        }

        return $primaryTabsElement;
    }

    /**
     * @return \Behat\Mink\Element\NodeElement[]
     */
    protected function getPrimaryTabsLinks(): array
    {
        $linksFinder = $this->getFinder('drupal.core.tabs.primary_tabs_links');

        return $this
            ->getPrimaryTabsElement(true)
            ->findAll($linksFinder['selector'], $linksFinder['locator']);
    }

    /**
     * @return string[]
     */
    protected function getPrimaryTabsLinkLabels(): array
    {
        $linkLabels = [];
        foreach ($this->getPrimaryTabsLinks() as $linkElement) {
            $linkLabels[] = $linkElement->getText();
        }

        return $linkLabels;
    }
}
