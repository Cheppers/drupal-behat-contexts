<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Drupal\Core\Cache\Cache;
use Drupal\Core\PhpStorage\PhpStorageFactory;

class CoreCache extends Base
{

    use CoreContentEntityContextTrait;

    /**
     * @Given /^all the cache bins are empty$/
     */
    public function doCacheRebuild()
    {
        //drupal_flush_all_caches();
        $this
            ->doCacheRebuildBins()
            ->doCacheRebuildAssets()
            ->doCacheRebuildStatic()
            ->doCacheRebuildKernelContainer()
            ->doCacheRebuildTwigPhp()
            ->doCacheRebuildPluginDefinitions();

        return $this;
    }

    protected function doCacheRebuildBins()
    {
        $module_handler = \Drupal::moduleHandler();
        // Flush all persistent caches.
        // This is executed based on old/previously known information, which is
        // sufficient, since new extensions cannot have any primed caches yet.
        $module_handler->invokeAll('cache_flush');
        foreach (Cache::getBins() as $service_id => $cache_backend) {
            $cache_backend->deleteAll();
        }

        return $this;
    }

    protected function doCacheRebuildAssets()
    {
        \Drupal::service('asset.css.collection_optimizer')->deleteAll();
        \Drupal::service('asset.js.collection_optimizer')->deleteAll();
        _drupal_flush_css_js();

        return $this;
    }

    protected function doCacheRebuildStatic()
    {
        drupal_static_reset();

        return $this;
    }

    protected function doCacheRebuildKernelContainer()
    {
        \Drupal::service('kernel')->invalidateContainer();

        return $this;
    }

    protected function doCacheRebuildTwigPhp()
    {
        PhpStorageFactory::get('twig')->deleteAll();

        return $this;
    }

    protected function doCacheRebuildPluginDefinitions()
    {
        \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

        return $this;
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
