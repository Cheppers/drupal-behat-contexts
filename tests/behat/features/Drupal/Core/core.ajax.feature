Feature: Some helpful description.

    @api @javascript
    Scenario: Some helpful description.
        Given I am logged in as a user with the "Administrator" role
        And I am on "/node/add/article"
        And I should not see an "[name='field_image[0][alt]']" element
        When I attach the file "normal.01.png" to "Image"
        And I wait 3 second for AJAX to finish
        Then I should see the field "field_image[0][alt]"
