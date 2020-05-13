<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Context\Base;
use Drupal;
use PHPUnit\Framework\Assert;

class CoreSystem extends Base
{

    /**
     * {@inheritdoc}
     */
    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.core.maintenance.page_title' => [
                'selector' => 'css',
                'locator' => '#page-title',
            ],
            'drupal.core.maintenance.element' => [
                'selector' => 'css',
                'locator' => 'body.maintenance-page',
            ],
        ];
    }

    /**
     * @AfterScenario @apiMaintenanceModeOff
     */
    public static function hookAfterScenarioMaintenanceModeOff()
    {
        static::setSiteMaintenanceMode(false);
    }

    protected static function setSiteMaintenanceMode(bool $state)
    {
        Drupal::state()->set('system.maintenance_mode', $state);
    }

    /**
     * @Given /^the site is in maintenance mode$/
     * @When /^I turn the site into maintenance mode$/
     */
    public function doSiteMaintenanceModeOn()
    {
        static::setSiteMaintenanceMode(true);
    }

    /**
     * @Given /^the site is not in maintenance mode$/
     * @When /^I turn the site into live mode$/
     */
    public function doSiteMaintenanceModeOff()
    {
        static::setSiteMaintenanceMode(false);
    }

    /**
     * @Then /^I see that the site is under maintenance$/
     */
    public function assertSiteMaintenanceModeOn()
    {
        $pageTitleFinder = $this->getFinder('drupal.core.maintenance.page_title');
        $pageTitleElement = $this
            ->getSession()
            ->getPage()
            ->find($pageTitleFinder['selector'], $pageTitleFinder['locator']);
        Assert::assertNotEmpty($pageTitleElement);

        $maintenanceModeFinder = $this->getFinder('drupal.core.maintenance.element');
        $maintenanceModeElement = $this
            ->getSession()
            ->getPage()
            ->find($maintenanceModeFinder['selector'], $maintenanceModeFinder['locator']);
        Assert::assertNotEmpty($maintenanceModeElement);
    }
}
