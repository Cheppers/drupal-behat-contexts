@api
@javascript
Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\CoreCkeditor

    Scenario: Fill in a wysiwyg field
        Given I am logged in as a user with the "Administrator" role
        And I am on "/node/add/article"
        When I fill in "Title" with "My article 01"
        And I fill in wysiwyg on field "body[0][value]" with "My body text"
        And I press the "Save" button
        Then I should see the message "Article My article 01 has been created."
        And the page title is "My article 01"
        And I should see the text "My body text"
