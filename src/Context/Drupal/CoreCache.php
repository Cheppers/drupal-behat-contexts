<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;

class CoreCache extends Base
{

    use CoreContentEntityContextTrait;

    /**
     * @Given /^all the cache bins are empty$/
     */
    public function doCacheRebuild()
    {
        drupal_flush_all_caches();
    }

    /**
     * @Given /^all cached content which are related to "(?P<cacheTag>[^"]+)" cache tag are invalidated$/
     */
    public function doCacheFlushByTag(string $cacheTag)
    {
        $cacheTag = $this->processCacheTag($cacheTag);

        /** @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface $s */
        $cacheTagsInvalidator = \Drupal::service('cache_tags.invalidator');
        $cacheTagsInvalidator->invalidateTags([$cacheTag]);
    }

    protected function processCacheTag(string $cacheTag): string
    {
        $parts = explode(':', $cacheTag);

        $etm = \Drupal::entityTypeManager();
        if ($etm->hasDefinition($parts[0]) && !empty($parts[1])
            && !is_numeric($parts[1])) {
            $entity = $this->getContentEntityByLabel($parts[0], $parts[1]);
            if ($entity) {
                $parts[1] = $entity->id();
                $cacheTag = implode(':', $parts);
            }
        }

        return $cacheTag;
    }
}
