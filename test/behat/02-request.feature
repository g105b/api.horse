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

  Scenario: Scheme is forced
    Given I am on the homepage
    And I fill in the "request-editor" input "endpoint" with "example.com"
    And I submit the form
    Then the "request-editor" input "endpoint" should contain "http://example.com"

  Scenario: Add multiple requests
    Given I am on the homepage
    And I fill in the "request-editor" input "name" with "First request"
    And I fill in the "request-editor" input "endpoint" with "https://example.com?request=1"
    And I submit the form
    And I follow "New request"
    When I fill in the "request-editor" input "name" with "Second request"
    And I fill in the "request-editor" input "endpoint" with "https://example.com?request=2"
    And I submit the form
    And I follow "New request"
    When I fill in the "request-editor" input "name" with "Third request"
    And I fill in the "request-editor" input "endpoint" with "https://example.com?request=3"
    And I submit the form
    When I go to the homepage
    Then I should see "First request" in the "request-sidebar"
    And I should see "Second request" in the "request-sidebar"
    And I should see "Third request" in the "request-sidebar"

