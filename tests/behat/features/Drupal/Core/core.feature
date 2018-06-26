Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\Core

    @javascript
    Scenario: Assert page title
        Given I am at "/filter/tips"
        Then the page title is "Compose tips"
