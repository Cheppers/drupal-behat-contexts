<?php

namespace Cheppers\DrupalExtension\Component\Drupal;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldStorageConfig;

trait CoreContentEntityContextTrait
{
    protected function getContentEntityByLabel(
        string $entityTypeId,
        string $label,
        string $fieldName = ''
    ): ?ContentEntityInterface {
        $etm = \Drupal::entityTypeManager();
        $storage = $etm->getStorage($entityTypeId);
        $entityType = $etm->getDefinition($entityTypeId);

        if (!$fieldName) {
            switch ($entityTypeId) {
                case 'user':
                    $fieldName = 'name';
                    break;

                default:
                    $fieldName = $etm
                        ->getDefinition($entityTypeId)
                        ->getKey('label');
                    break;
            }
        }

        // https://www.drupal.org/project/drupal/issues/2986322
        $storage->resetCache();

        $entities = $storage
            ->loadByProperties(
                [
                    $fieldName => $label,
                ],
            );

        // @todo Multiple result.
        $entity = reset($entities);
        if ($entity === false) {
            return null;
        }

        return $entity;
    }

    protected function getContentEntityUrlByLabel(
        string $entityTypeId,
        string $label,
        $relation = 'canonical',
        $options = []
    ): string {
        $entity = $this->getContentEntityByLabel($entityTypeId, $label);

        if (!$entity) {
            return '';
        }

        return $entity
            ->toUrl($relation, $options)
            ->toString();
    }

    protected function createContentEntity(
        string $entityTypeId,
        array $fieldValues
    ): ContentEntityInterface {
        /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
        $efm = \Drupal::service('entity_field.manager');
        $etm = \Drupal::entityTypeManager();
        $entityType = $etm->getDefinition($entityTypeId);
        $bundleKey = $entityType->hasKey('id') ? $entityType->getKey('bundle')
            : '';
        $bundleId = $fieldValues[$bundleKey] ?? $entityTypeId;

        $baseFields = $efm->getBaseFieldDefinitions($entityTypeId);
        $fields = $efm->getFieldDefinitions($entityTypeId, $bundleId);

        $values = [];
        foreach ($fieldValues as $fieldName => $fieldValue) {
            $fieldId = "$entityTypeId:$bundleId:$fieldName";
            $field = $baseFields[$fieldName] ?? $fields[$fieldName];

            switch ($fieldId) {
                case 'commerce_product:moc:variations':
                    $values[$fieldName] = $fieldValue;
                    break;
            }

            if (isset($values[$fieldName])) {
                continue;
            }

            // @todo Process values based on the type of the destination field.
            switch ($field->getType()) {
                case 'entity_reference':
                    if ($fieldName === $bundleKey) {
                        // @todo Do the same if the targetEntityTypeId is a ConfigEntity.
                        $values[$fieldName] = $fieldValue;
                    } else {
                        $targetEntityTypeId = $field->getSetting('target_type');
                        if (!is_array($fieldValue)) {
                            $fieldValue = [$fieldValue];
                        }

                        foreach (array_keys($fieldValue) as $delta) {
                            $values[$fieldName][$delta] = $this->getContentEntityByLabel(
                                $targetEntityTypeId,
                                $fieldValue[$delta]
                            )->id();
                        }
                    }
                    break;

                case 'file':
                    $targetEntityTypeId = 'file';
                    if (!is_array($fieldValue)) {
                        $fieldValue = [$fieldValue];
                    }

                    foreach (array_keys($fieldValue) as $delta) {
                        $values[$fieldName][$delta] = $this->getContentEntityByLabel(
                            $targetEntityTypeId,
                            $fieldValue[$delta]
                        )->id();
                    }
                    break;

                default:
                    $values[$fieldName] = $fieldValue;
                    break;
            }
        }

        /** @var ContentEntityInterface $contentEntity */
        $contentEntity = \Drupal
            ::entityTypeManager()
            ->getStorage($entityTypeId)
            ->create($values);

        $contentEntity->save();

        return $contentEntity;
    }

    /**
     * @return \Drupal\field\FieldStorageConfigInterface[]
     */
    protected function getFields(string $entityTypeId): array
    {
        $fields = [];

        $allFields = FieldStorageConfig::loadMultiple();
        /** @var \Drupal\field\FieldStorageConfigInterface $field */
        foreach ($allFields as $field) {
            if ($field->getTargetEntityTypeId() !== $entityTypeId) {
                continue;
            }

            $fields[$field->id()] = $field;
        }

        return $fields;
    }

    protected function keyValuePairsToNestedArray(array $keyValuePairs): array
    {
        $values = [];
        foreach ($keyValuePairs as $keyParts => $value) {
            $parents = explode(':', $keyParts);
            $values = array_replace_recursive($values, $this->buildNestedArray($parents, $value));
        }

        return $values;
    }

    protected function buildNestedArray(array $parents, $value): array
    {
        $key = array_shift($parents);

        return [
            $key => $parents ? $this->buildNestedArray($parents, $value) : $value,
        ];
    }
}
