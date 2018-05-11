<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Component\ClickElementContextTrait;
use Cheppers\DrupalExtension\Component\ScrollToElementContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit_Framework_Assert as Assert;

/**
 * Before drupal/core:8.4 there were dropdown buttons on the node edit form.
 */
class CoreDropdownButton extends Base
{

    use ScrollToElementContextTrait;
    use ClickElementContextTrait;

    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.core.dropdown_button.group' => [
                'selector' => 'css',
                'locator' => 'div[data-drupal-selector="edit-{{ group }}"]',
            ],
            'drupal.core.dropdown_button.toggler' => [
                'selector' => 'css',
                'locator' => 'button.dropdown-toggle[data-toggle="dropdown"]',
            ],
            'drupal.core.dropdown_button.link' => [
                'selector' => 'css',
                'locator' => 'ul.dropdown-menu > li > a[data-dropdown-target]',
            ],
            'drupal.core.dropdown_button.link_to' => [
                'selector' => 'css',
                'locator' => 'a[data-dropdown-target="#{{ button_id }}"]',
            ],
            'drupal.core.dropdown_button.button' => [
                'selector' => 'css',
                'locator' => '.button[name="{{ name }}"][value="{{ value }}"]',
            ],
        ];

        return $this;
    }

    /**
     * @Then the available dropdown buttons are
     * @Then the available dropdown buttons in the :group group are
     */
    public function assertDropdownButtonsTable(TableNode $table, string $group = 'actions-save')
    {
        Assert::assertSame(
            $table->getColumn(0),
            $this->getAvailableDropdownButtons($group)
        );
    }

    /**
     * @Then the list of available dropdown buttons is :labels
     * @Then the list of available dropdown buttons is :labels in the :group group are
     */
    public function assertDropdownButtonsList(string $labels, string $group = 'actions-save')
    {
        $labels = $labels ? preg_split('/;(\s*)/', $labels) : [];

        Assert::assertSame($labels, $this->getAvailableDropdownButtons($group));
    }

    /**
     * @When I click on the :group dropdown buttons toggler
     */
    public function doClickOnDropdownButtonToggler(string $group)
    {
        $locator = $this->getCssLocatorForDropdownButtonToggler($group);
        $this->clickElementByCssLocator($locator);
    }

    /**
     * @When I click on the :value dropdown button
     * @When I click on the :value dropdown button in the :group
     */
    public function doClickOnDropdownButton(string $value, string $group = 'actions-save')
    {
        $buttonFinder = $this->getFinder(
            'drupal.core.dropdown_button.button',
            [
                '{{ name }}' => 'op',
                '{{ value }}' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false),
            ]
        );

        $locator = $this->getCssLocatorForDropdownButtonGroup($group) . ' ' . $buttonFinder['locator'];

        $this->clickElementByCssLocator($locator);
    }

    /**
     * @When I click on the :value dropdown button link
     * @When I click on the :value dropdown button link in the :group
     */
    public function doClickOnDropdownButtonLink(string $value, string $group = 'actions-save')
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $buttonFinder = $this->getFinder(
            'drupal.core.dropdown_button.button',
            [
                '{{ name }}' => 'op',
                '{{ value }}' => htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false),
            ]
        );

        $buttonLocator = $this->getCssLocatorForDropdownButtonGroup($group) . ' ' . $buttonFinder['locator'];
        $button = $page->find('css', $buttonLocator);

        if (!$button) {
            throw new ElementNotFoundException($session, 'element', 'css', $buttonLocator);
        }

        $linkToButtonFinder = $this->getFinder(
            'drupal.core.dropdown_button.link_to',
            [
                '{{ button_id }}' => $button->getAttribute('id'),
            ]
        );

        $this->clickElementByCssLocator($linkToButtonFinder['locator']);
    }

    /**
     * @return string[]
     */
    protected function getAvailableDropdownButtons(string $group): array
    {
        $labels = [];
        $targetFinder = $this->getFinder('drupal.core.dropdown_button.link');
        $locator = $this->getCssLocatorForDropdownButtonGroup($group) . ' ' . $targetFinder['locator'];

        /** @var \Behat\Mink\Element\NodeElement[] $elements */
        $elements = $this
            ->getSession()
            ->getPage()
            ->findAll('css', $locator);

        foreach ($elements as $element) {
            $labels[] = $element->getHtml();
        }

        return $labels;
    }

    protected function getCssLocatorForDropdownButtonGroup(string $group): string
    {
        $finder = $this->getFinder(
            'drupal.core.dropdown_button.group',
            [
                '{{ group }}' => $group,
            ]
        );

        return $finder['locator'];
    }

    protected function getCssLocatorForDropdownButtonToggler(string $group): string
    {
        $groupLocator = $this->getCssLocatorForDropdownButtonGroup($group);
        $togglerFinder = $this->getFinder('drupal.core.dropdown_button.toggler');

        return "{$groupLocator} {$togglerFinder['locator']}";
    }
}
