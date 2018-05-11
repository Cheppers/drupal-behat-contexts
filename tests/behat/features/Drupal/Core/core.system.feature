Feature: Some helpful description.

    @api @javascript @apiMaintenanceModeOff
    Scenario: Some helpful description.
        Given the site is in maintenance mode
        And all the cache bins are empty
        And I am an anonymous user
        When I am on the homepage
        Then I see that the site is under maintenance
