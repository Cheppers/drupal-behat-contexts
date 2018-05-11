<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Context\Base;

class CoreVerticalTab extends Base
{
    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.core.vertical_tabs.tab_label' => [
                'selector' => 'xpath',
                'locator' => '//ul[@class="nav nav-tabs vertical-tabs-list"]/li/a/span[text() = "{{ tabLabel }}"]',
            ],
        ];

        return $this;
    }

    /**
     * @When /^I activate the "(?P<label>[^"]+)" vertical tab$/
     */
    public function doActivateVerticalTab(string $label)
    {
        $tabLabelFinder = $this->getFinder(
            'drupal.core.vertical_tabs.tab_label',
            [
                '{{ tabLabel }}' => $this->escapeXpathValue($label),
            ]
        );

        $tabLabelElement = $this
            ->getSession()
            ->getPage()
            ->find($tabLabelFinder['selector'], $tabLabelFinder['locator']);

        if (!$tabLabelElement) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $tabLabelFinder['selector'],
                $tabLabelFinder['locator']
            );
        }

        $tabLabelElement->click();
    }
}
