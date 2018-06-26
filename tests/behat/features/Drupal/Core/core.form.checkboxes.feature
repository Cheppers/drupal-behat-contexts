Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\CoreFormCheckboxes

    @api @javascript
    Scenario: Assert steps
        Given I am logged in as a user with the "Administrator" role
        And I am on "/admin/config/content/formats/manage/basic_html"
        Then the "Roles" checkboxes group has the following checkboxes:
            | Anonymous user     |
            | Authenticated user |
            | Administrator      |
        And the "Roles" checkboxes group contains the following checkboxes:
            | Anonymous user |
            | Administrator  |
        And the "Roles" checkboxes group does not contain any of the following checkboxes:
            | Foo |
            | Bar |
        And the state of the checkboxes in the "Roles" checkboxes group is the following:
            | Anonymous user     |               |
            | Authenticated user | authenticated |
            | Administrator      | administrator |
