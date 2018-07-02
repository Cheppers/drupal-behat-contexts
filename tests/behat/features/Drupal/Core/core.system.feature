Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\CoreCache

    @api @javascript @apiMaintenanceModeOff
    Scenario: Use maintenance mode as an anonymous user
        Given I am on the homepage
        And the site is in maintenance mode
        And all the cache bins are empty
        When I am on the homepage
        Then I see that the site is under maintenance
