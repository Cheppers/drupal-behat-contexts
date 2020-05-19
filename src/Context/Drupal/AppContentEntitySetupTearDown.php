<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Context\Base;
use Drupal;
use Drupal\Core\Entity\EntityTypeInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

class AppContentEntitySetupTearDown extends Base
{
    /**
     * @var int[]
     */
    protected static $entityTypeWeights = [];

    /**
     * @return array
     * @var int[]
     */
    protected static function getEntityTypeWeights(): array
    {
        if (!static::$entityTypeWeights) {
            static::$entityTypeWeights = array_flip([
                'block_content',

                'node',
                'profile',
                'search_api_task',

                'commerce_order',
                'commerce_order_item',
                'commerce_shipment',
                'commerce_product',
                'commerce_product_variation',
                'commerce_product_attribute_value',
                'commerce_store',
                'commerce_shipping_method',
                'commerce_payment',
                'commerce_payment_method',
                'commerce_log',

                'content_moderation_state',
                'media',
                'file',
                'crop',
                'user',

                'taxonomy_term',
                'mailchimp_campaign',
                'menu_link_content',
            ]);
        }

        return static::$entityTypeWeights;
    }

    protected static function compareEntityTypesByWeight(string $a, string $b): int
    {
        $weights = static::getEntityTypeWeights();

        return ($weights[$a] ?? 0) <=> ($weights[$b] ?? 0);
    }

    /**
     * File names.
     *
     * @var string[]
     */
    protected $unManagedFiles = [];

    /**
     * Existing Content Entity IDs before the scenario.
     *
     * @var int[]
     */
    protected $latestContentEntityIds = [];

    /**
     * Existing Alphabetic Content Entity IDs before the scenario.
     *
     * @var string[]
     */
    protected $latestAlphabeticEntityIds = [];

    /**
     * @BeforeScenario
     */
    public function hookBeforeScenario()
    {
        try {
            $this->visitPath('/');
        } catch (Exception $e) {
            // Do nothing.
        }
        $this
            ->getAlphabeticEntityId()
            ->initLatestContentEntityIds();
    }

    /**
     * @AfterScenario
     */
    public function hookAfterScenario()
    {
        $this
            ->cleanAlphabeticEntities()
            ->cleanNewContentEntities()
            ->cleanUnManagedFiles();
    }

    /**
     * @return $this
     */
    protected function getAlphabeticEntityId()
    {
        $etm = Drupal::entityTypeManager();
        $entityTypes = $etm->getDefinitions();

        foreach ($entityTypes as $entityType) {
            if (!$this->isContentEntityType($entityType)) {
                continue;
            }

            $ids = $etm
                ->getStorage($entityType->id())
                ->getQuery()
                ->execute();
            if (preg_match("/[a-z]/i", reset($ids))) {
                $this->latestAlphabeticEntityIds[$entityType->id()] = $ids;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function cleanAlphabeticEntities()
    {
        $etm = Drupal::entityTypeManager();
        $entityTypes = $etm->getDefinitions();
        $afterScenarioEntities = [];
        foreach ($entityTypes as $entityType) {
            if (!$this->isContentEntityType($entityType)) {
                continue;
            }
            $ids = $etm
                ->getStorage($entityType->id())
                ->getQuery()
                ->execute();
            if (preg_match("/[a-z]/i", reset($ids))) {
                $afterScenarioEntities[$entityType->id()] = $ids;
            }
        }
        foreach ($afterScenarioEntities as $entityTypeId => $afterScenarioEntity) {
            $diff[$entityTypeId] = array_diff(
                $afterScenarioEntities[$entityTypeId],
                (array) $this->latestAlphabeticEntityIds[$entityTypeId]
            );
            $storage = $etm->getStorage($entityTypeId);
            $storage->delete($storage->loadMultiple($diff[$entityTypeId]));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function initLatestContentEntityIds()
    {
        $etm = Drupal::entityTypeManager();
        $entityTypes = $etm->getDefinitions();

        foreach ($entityTypes as $entityType) {
            if (!$this->isContentEntityType($entityType)) {
                continue;
            }

            $ids = $etm
                ->getStorage($entityType->id())
                ->getQuery()
                ->sort($entityType->getKey('id'), 'DESC')
                ->range(0, 1)
                ->execute();

            if (preg_match("/[a-z]/i", reset($ids))) {
                continue;
            }
            $this->latestContentEntityIds[$entityType->id()] = (int) reset(
                $ids
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function cleanNewContentEntities()
    {
        $etm = Drupal::entityTypeManager();

        uksort($this->latestContentEntityIds, [static::class, 'compareEntityTypesByWeight']);

        foreach ($this->latestContentEntityIds as $entityTypeId => $entityId) {
            $entityType = $etm->getDefinition($entityTypeId);
            $storage = $etm->getStorage($entityTypeId);
            $ids = $storage
                ->getQuery()
                ->condition($entityType->getKey('id'), $entityId, '>')
                ->execute();

            if (!$ids) {
                continue;
            }

            $storage->delete($storage->loadMultiple($ids));
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function cleanUnManagedFiles()
    {
        $fs = new Filesystem();
        $drupalRoot = Drupal::root();
        while (($fileName = array_pop($this->unManagedFiles))) {
            $fs->remove("$drupalRoot/$fileName");
        }

        return $this;
    }

    protected function isContentEntityType(EntityTypeInterface $entityType): bool
    {
        return $entityType->hasKey('id') && $entityType->getBaseTable();
    }
}
