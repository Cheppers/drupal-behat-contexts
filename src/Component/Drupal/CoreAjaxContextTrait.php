<?php

namespace Cheppers\DrupalExtension\Component\Drupal;

trait CoreAjaxContextTrait
{
    protected function waitForAjaxToFinish(float $seconds)
    {
        $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
        (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      );
    }());
JS;
        $result = $this->getSession()->wait($seconds * 1000, $condition);
        if (!$result) {
            throw new \RuntimeException('Unable to complete AJAX request.');
        }
    }
}
