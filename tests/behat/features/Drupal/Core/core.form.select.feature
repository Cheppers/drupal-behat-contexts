@api
@javascript
Feature: Test steps in \Cheppers\DrupalExtension\Context\Drupal\CoreFormSelect

    Scenario: Assert steps
        Given I am logged in as a user with the "Administrator" role
        And I am on "/admin/config/content/formats/manage/basic_html"
        Then the "Text editor" select list has the following options:
            | None       |           |
            | CKEditor 5 | ckeditor5 |
