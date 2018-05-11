<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit_Framework_Assert as Assert;

/**
 * @todo Add supports for option groups.
 */
class CoreFormSelect extends Base
{

    /**
     * @Then /^the "(?P<label>[^"]+)" select list has the following options:$/
     */
    public function assertSelectTable(TableNode $table, string $label)
    {
        $actual = $this->getOptions($this->getSelectElement($label, true));
        Assert::assertSame($table->getColumn(0), array_keys($actual));

        $firstRow = $table->getRow(0);
        $columnCount = count($firstRow);
        if ($columnCount > 1) {
            Assert::assertSame($table->getColumn(1), array_values($actual));
        }
    }

    /**
     * @Then /^I see in the "(?P<label>[^"]+)" select list the following options (?P<csv>.*)$/
     */
    public function assertSelectCsv(string $csv, string $label)
    {
        $this->assertSelect(str_getcsv($csv), $label);
    }

    protected function assertSelect(array $expected, string $label)
    {
        $selectElement = $this
            ->getSession()
            ->getPage()
            ->findField($label);

        Assert::assertNotNull(
            $selectElement,
            sprintf('Select element with "%s" label is exists', $label)
        );
        Assert::assertSame('select', $selectElement->getTagName());

        /** @var \Behat\Mink\Element\NodeElement[] $optionElements */
        $optionElements = $selectElement->findAll('xpath', '//option');
        $optionLabels = [];
        foreach ($optionElements as $optionElement) {
            $optionLabels[] = $optionElement->getText();
        }

        Assert::assertSame($expected, $optionLabels);
    }

    protected function getOptions(NodeElement $selectElement): array
    {
        /** @var \Behat\Mink\Element\NodeElement[] $optionElements */
        $optionElements = $selectElement->findAll('xpath', '//option');
        $options = [];
        foreach ($optionElements as $optionElement) {
            $label = $optionElement->getText();
            $options[$label] = $optionElement->getValue();
        }

        return $options;
    }

    protected function getSelectElement(string $label, bool $required = false): ?NodeElement
    {
        $selectElement = $this
            ->getSession()
            ->getPage()
            ->findField($label);

        if (!$required && !$selectElement) {
            return null;
        }

        Assert::assertNotNull(
            $selectElement,
            sprintf('Select element with "%s" label is exists', $label)
        );

        Assert::assertSame('select', $selectElement->getTagName());

        return $selectElement;
    }
}
