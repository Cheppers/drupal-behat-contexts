<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit_Framework_Assert as Assert;

class CoreFormCheckboxes extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.core.checkboxes.wrapper' => [
                'selector' => 'xpath',
                'locator' => '//fieldset[//div[@class="form-checkboxes"]]',
            ],
            'drupal.core.checkboxes.legend' => [
                'selector' => 'xpath',
                'locator' => '//legend',
            ],
            'drupal.core.checkboxes.label' => [
                'selector' => 'xpath',
                'locator' => '//label',
            ],
        ];

        return $this;
    }

    /**
     * @Then /^the "(?P<groupLabel>[^"]+)" checkboxes group has the following checkboxes:$/
     */
    public function assertCheckboxesSameTable(TableNode $table, string $groupLabel)
    {
        $expected = $table->getColumn(0);
        $actual = $this->getCheckboxLabels($this->getCheckboxesWrapper($groupLabel, true));
        Assert::assertSame($expected, $actual);
    }

    /**
     * @Then /^the "(?P<groupLabel>[^"]+)" checkboxes group contains the following checkboxes:$/
     */
    public function assertCheckboxesContainsTable(TableNode $table, string $groupLabel)
    {
        $expected = $table->getColumn(0);
        $actual = $this->getCheckboxLabels($this->getCheckboxesWrapper($groupLabel, true));
        Assert::assertSame($expected, array_intersect($expected, $actual));
    }

    /**
     * @Then /^the "(?P<groupLabel>[^"]+)" checkboxes group does not contain any of the following checkboxes:$/
     */
    public function assertCheckboxesNotContainsTable(TableNode $table, string $groupLabel)
    {
        $expected = $table->getColumn(0);
        $actual = $this->getCheckboxLabels($this->getCheckboxesWrapper($groupLabel, true));
        Assert::assertEmpty(array_intersect($expected, $actual));
    }

    /**
     * @Then /^the state of the checkboxes in the "(?P<groupLabel>[^"]+)" checkboxes group is the following:$/
     */
    public function assertCheckboxesStateTable(TableNode $table, string $groupLabel)
    {
        $expected = $this->parseStatesFromTable($table);
        $actual = $this->getCheckboxStates($this->getCheckboxesWrapper($groupLabel, true));
        Assert::assertSame($expected, $actual);
    }

    protected function parseStatesFromTable(TableNode $table): array
    {
        $states = [];
        foreach ($table->getRows() as $row) {
            $states[$row[0]] = $row[1] ?: null;
        }

        return $states;
    }

    protected function getCheckboxesWrapper(string $groupLabel, bool $required = false): ?NodeElement
    {
        $checkboxesWrapperFinder = $this->getFinder('drupal.core.checkboxes.wrapper');

        /** @var \Behat\Mink\Element\NodeElement[] $wrappers */
        $wrappers = $this
            ->getSession()
            ->getPage()
            ->findAll($checkboxesWrapperFinder['selector'], $checkboxesWrapperFinder['locator']);

        $legendFinder = $this->getFinder('drupal.core.checkboxes.legend');

        // @todo More than one checkboxes can be exists with the same label.
        foreach ($wrappers as $wrapper) {
            $legendElement = $wrapper->find($legendFinder['selector'], $legendFinder['locator']);
            if ($legendElement && $legendElement->getText() === $groupLabel) {
                return $wrapper;
            }
        }

        if ($required) {
            throw new ExpectationException(
                sprintf('There is no checkboxes with label: "%s"', $groupLabel),
                $this->getSession()
            );
        }

        return null;
    }

    protected function getCheckboxLabels(NodeElement $checkboxesWrapper): array
    {
        $checkboxLabelElements = $this->getCheckboxLabelElements($checkboxesWrapper);
        $checkboxLabels = [];
        foreach ($checkboxLabelElements as $checkboxLabelElement) {
            $checkboxLabels[] = $checkboxLabelElement->getText();
        }

        return $checkboxLabels;
    }

    protected function getCheckboxStates(NodeElement $checkboxesWrapper): array
    {
        $checkboxLabelElements = $this->getCheckboxLabelElements($checkboxesWrapper);
        $checkboxStates = [];
        foreach ($checkboxLabelElements as $checkboxLabelElement) {
            $label = $checkboxLabelElement->getText();
            $checkbox = $checkboxesWrapper->findById($checkboxLabelElement->getAttribute('for'));
            $checkboxStates[$label] = $checkbox->getValue();
        }

        return $checkboxStates;
    }

    /**
     * @return \Behat\Mink\Element\NodeElement[]
     */
    protected function getCheckboxLabelElements(NodeElement $checkboxesWrapper): array
    {
        $labelFinder = $this->getFinder('drupal.core.checkboxes.label');

        return $checkboxesWrapper->findAll(
            $labelFinder['selector'],
            $labelFinder['locator']
        );
    }
}
