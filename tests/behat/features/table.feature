@api
@javascript
Feature: Test steps in \Cheppers\DrupalExtension\Context\Table

    @knownBug
    Scenario: Click on link
        Given I am logged in as a user with the "Administrator" role
        And "page" content:
            | title      | status |
            | My Page 01 | 1      |
        And I am on "/admin/content"
        When I click on the 1st link in the "Title" column of the 1st row
        Then the page title is "My Page 01"
