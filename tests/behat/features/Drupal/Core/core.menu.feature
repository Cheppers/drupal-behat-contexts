@api
@javascript
Feature: Test steps from \Cheppers\DrupalExtension\Context\Drupal\CoreMenu

    Scenario: Assert menu links
        Given I am logged in as a user with the "Administrator" role
        And I am on the homepage
        Then I should see the following links in the "User account menu" menu:
            | My account |
            | Log out    |
        And I should see the following links in the "Main navigation" menu:
            | Home |
        And I should see the following links in the "Tools" menu:
            | Add content |
