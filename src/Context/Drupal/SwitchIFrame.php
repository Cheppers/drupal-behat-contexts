<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Context\Base;

class SwitchIFrame extends Base {

  /**
   * Select a frame by its name or ID.
   *
   * @When I switch to iframe :name
   */
  public function switchToIframe(string $name) {
    $this->getSession()->switchToIFrame($name);
  }

  /**
   * Select a frame by its name or ID.
   *
   * @When I switch back to the main frame
   */
  public function switchBackToTheMainFrame() {
    $this->getSession()->switchToIFrame();
  }

}
