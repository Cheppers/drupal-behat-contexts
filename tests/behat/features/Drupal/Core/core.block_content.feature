@api
@javascript
Feature: Some helpful description.

    Scenario: Some helpful description.
        Given block_content:
            | type | basic    |
            | info | my label |
        And I am logged in as a user with the "Administrator" role
        When I go to "/admin/structure/block/block-content"
        Then I should see the text "my label"

        Given I am editing the block content "my label"
        Then the "Block description" field should contain "my label"
