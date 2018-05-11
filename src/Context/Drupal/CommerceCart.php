<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Component\XpathContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit_Framework_Assert as Assert;

class CommerceCart extends Base
{
    use CoreContentEntityContextTrait;
    use XpathContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.commerce_cart.quantity' => [
                'locator' => 'Quantity',
            ],
            'drupal.commerce_cart.row_by_order_item_title' => [
                'selector' => 'xpath',
                'locator' => '//a[text() = "{{ title }}"]/ancestor::div[@class="cart-row container-wrapper"]',
            ],
            'drupal.commerce_cart.block.number_of_items' => [
                'selector' => 'xpath',
                'locator' => '//span[@class="cart-block--summary__count cart-item-count text-center circle"]',
            ],
            'drupal.commerce_cart.order_total.line' => [
                'selector' => 'xpath',
                'locator' => '//div[starts-with(@class, "order-total-line order-total-line__{{ line }}")]/span[2]',
            ],
        ];

        return $this;
    }

    /**
     * @When /^I increase the quantity of the "(?P<title>[^"]+)" to (?P<newValue>\d+)$/
     */
    public function doQuantityIncreaseDecrease(string $title, string $newValue)
    {
        $carRow = $this->getCartRow($title);
        $quantityFinder = $this->getFinder('drupal.commerce_cart.quantity');
        $quantityElement = $carRow->findField($quantityFinder['locator']);
        if (!$quantityElement) {
            throw new ElementNotFoundException(
                $this->getSession()->getDriver(),
                'form field',
                'id|name|label|value|placeholder',
                $quantityFinder['locator']
            );
        }

        $quantityElement->setValue($newValue);
        $quantityElement->keyDown(9);
        $quantityElement->keyUp(9);
    }

    /**
     * @Then /^I should see (?P<number>\d+) in cart widget's counter$/
     */
    public function assertCartWidgetNumberOfItemsIs(int $number)
    {
        $numOfItemsFinder = $this->getFinder('drupal.commerce_cart.block.number_of_items');

        $this->assertNumberByXpathSelector($number, $numOfItemsFinder['locator']);
    }

    /**
     * @Then /^I should not see the cart widget's counter$/
     */
    public function assertCartWidgetNumberOfItemsNotVisible()
    {
        $numOfItemsFinder = $this->getFinder('drupal.commerce_cart.block.number_of_items');

        $element = $this
            ->getSession()
            ->getPage()
            ->find('xpath', $numOfItemsFinder['locator']);

        Assert::assertEmpty($element);
    }

    /**
     * @Then /^I should see that the subtotal is (?P<number>[\d]+(\.[\d]+)?)$/
     */
    public function assertOrderTotalLineSubtotal(float $number)
    {
        $this->assertOrderTotalLine($number, 'subtotal');
    }

    /**
     * @Then /^I should see that the shipping fee is (?P<number>[\d]+(\.[\d]+)?)$/
     */
    public function assertShippingFee(float $number)
    {
        $this->assertOrderTotalLine($number, 'adjustment');
    }

    /**
     * @Then /^I should see that the total is (?P<number>[\d]+(\.[\d]+)?)$/
     */
    public function assertTotal(float $number)
    {
        $this->assertOrderTotalLine($number, 'total');
    }

    protected function assertOrderTotalLine(float $number, string $line)
    {
        $lineFinder = $this->getFinder('drupal.commerce_cart.order_total.line', ['{{ line }}' => $line]);

        $this->assertNumberByXpathSelector($number, $lineFinder['locator']);
    }

    protected function getCartRow(
        string $title,
        bool $required = true
    ): ?NodeElement {
        $finder = $this->getFinder(
            'drupal.commerce_cart.row_by_order_item_title',
            [
                '{{ title }}' => $title,
            ]
        );

        $row = $this
            ->getSession()
            ->getPage()
            ->find($finder['selector'], $finder['locator']);

        if (!$row && $required) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $finder['selector'],
                $finder['locator']
            );
        }

        return $row;
    }
}
