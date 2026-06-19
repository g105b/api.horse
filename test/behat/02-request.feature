@javascript
Feature: Request
  Scenario: Requests are added to the sidebar
    Given I am on the homepage
    Then I should not see "My first request"
    And I fill in the "request-editor" input "name" with "My first request"
    And I submit the form
    Then I should see "My first request" in the "request-sidebar"

  Scenario: Create a basic request
    Given I am on the homepage
    And I fill in the "request-editor" input "name" with "My first request"
    And I fill in the "request-editor" input "endpoint" with "https://example.com"
    And I press "Send request"
    Then I should see "Completed in"
    And I should see "<h1>Example Domain</h1>"
