@javascript
Feature: Homepage
  Scenario: Homepage should show default content
    Given I am on the homepage
    Then I should see "Request editor"
    And I should see "Response viewer"
