<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Drupal\commerce_product\Entity\ProductInterface;
use PHPUnit\Framework\Assert;

class CommerceProduct extends Base
{
    use CoreContentEntityContextTrait;

    /**
     * @Given a CommerceProduct with the following fields
     */
    public function doCreateCommerceProduct(TableNode $fieldValues)
    {
        $fieldValues = $fieldValues->getRowsHash();
        $fieldValues += [
            'variations:type' => $fieldValues['type'],
            'variations:title' => $fieldValues['title'],
            'variations:uid' => $fieldValues['uid'],
        ];
        $fieldValues = $this->keyValuePairsToNestedArray($fieldValues);

        $productVariation = $this->createContentEntity(
            'commerce_product_variation',
            $fieldValues['variations']
        );

        $fieldValues['variations'] = [
            [
                'target_id' => $productVariation->id(),
            ],
        ];

        $product = $this->createContentEntity('commerce_product', $fieldValues);
        $productVariation
            ->set('product_id', $product->id())
            ->save();
    }

    /**
     * @Given I am on the :title product :link
     * @When  I go to the :title product :link
     */
    public function doGoToCommerceProductLink(string $title, string $link = 'canonical')
    {
        $url = $this->getCommerceProductUrlByTitle($title, $link);
        Assert::assertNotEmpty(
            $url,
            sprintf('No product with "%s" title is exists.', $title)
        );

        $this->visitPath($url);
    }

    /**
     * @Then /^the "(?P<productTitle>[^"]*)" product has product variations
     *     with the following titles:$/
     */
    public function assertProductHasProductVariationsWithTitles(string $productTitle, TableNode $table)
    {
        /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
        $product = $this->getCommerceProductByTitle($productTitle);
        Assert::assertNotNull(
            $product,
            sprintf('Product has been found with title "%s"', $productTitle)
        );

        $expectedTitles = $table->getColumn(0);
        $productVariations = $product->getVariations();

        Assert::assertSame(
            count($expectedTitles),
            count($productVariations),
            sprintf(
                'Expected number of product variations is %d. Actual: %d',
                count($expectedTitles),
                count($productVariations)
            )
        );

        foreach ($product->getVariations() as $delta => $productVariation) {
            Assert::assertSame(
                $expectedTitles[$delta],
                $productVariation->label(),
                sprintf(
                    'Expected product variation title is "%s". Actual: "%s"',
                    $expectedTitles[$delta],
                    $productVariation->label()
                )
            );
        }
    }

    /**
     * @When /^I replace the "(?P<variationTitle>[^"]+)" product variation with
     *     its clone$/
     */
    public function doCloneProductVariation(string $variationTitle)
    {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
        $variation = $this->getContentEntityByLabel('commerce_product_variation', $variationTitle);
        Assert::assertNotNull(
            $variation,
            sprintf('Product variation has been found with title "%s"', $variationTitle)
        );

        $product = $variation->getProduct();
        $product->removeVariation($variation);
        $product->save();

        $variationClone = $variation->createDuplicate();
        $variationClone->save();

        $product->addVariation($variationClone);
        $product->save();
    }

    protected function getCommerceProductByTitle(string $title): ?ProductInterface
    {
        $storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
        $ids = $storage
            ->getQuery()
            ->condition('title', $title)
            ->execute();

        // @todo Multiple result.
        $id = reset($ids);

        return $id ? $storage->load($id) : null;
    }

    protected function getCommerceProductUrlByTitle(
        string $title,
        string $relation = 'canonical',
        array $options = []
    ): string {
        $product = $this->getCommerceProductByTitle($title);

        if (!$product) {
            return '';
        }

        return $product
            ->toUrl($relation, $options)
            ->toString();
    }
}
