Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\CoreTabs

    @api @javascript
    Scenario: Assert steps
        Given I am logged in as a user with the "Administrator" role
        And I am on the homepage
        When I click "My account" in the "User account menu" menu
        Then I should see the following primary tabs:
            | View (active tab) |
            | Edit              |

    @api @javascript
    Scenario: Some helpful description.
      Given I am an anonymous user
      And I am on the homepage
      Then I see the text "No front page content has been created yet."
      And I should not see any primary tabs
