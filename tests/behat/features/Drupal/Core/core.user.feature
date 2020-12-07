@api
@javascript
Feature: Test user module related features

    Scenario: User create
        Given user:
            | name | a1                 |
            | mail | a1@behat.localhost |
        And I am logged in as a user with the "Administrator" role
        When I am on "/admin/people"
        Then I should see the text "a1"

    Scenario: User delete
        Given user:
            | name | a1                 |
            | mail | a1@behat.localhost |
        And the "a1" account is deleted with "Delete the account and its content." cancel method
        When I am logged in as a user with the "Administrator" role
        And I am on "/admin/people"
        Then I should not see the text "a1"
