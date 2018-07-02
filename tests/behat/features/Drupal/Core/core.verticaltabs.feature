Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\CoreVerticalTabs

    @api @javascript @ezt
    Scenario: Activate a tab.
        Given I am logged in as a user with the "Administrator" role
        And I am on "/admin/config/people/accounts"
        When I activate the "Password recovery" vertical tab in the "Emails"
        Then I should see the text "Edit the email messages sent to users who request a new password."

    @api @javascript @ezt
    Scenario: AssertSame tab labels.
        Given I am logged in as a user with the "Administrator" role
        And I am on "/admin/config/people/accounts"
        Then the "Emails" vertical tabs contains the following tabs:
            | Welcome (new user created by administrator) |
            | Welcome (awaiting approval)                 |
            | Admin (user awaiting approval)              |
            | Welcome (no approval required)              |
            | Account activation                          |
            | Account blocked                             |
            | Account cancellation confirmation           |
            | Account canceled                            |
            | Password recovery                           |
