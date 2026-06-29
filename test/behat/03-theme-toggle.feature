@javascript
Feature: Colour scheme
  Scenario: Theme toggle cycles through colour scheme overrides
    Given I am on the homepage
    Then the colour scheme override should be "system"
    When I press the theme toggle
    Then the colour scheme override should be "light"
    When I press the theme toggle
    Then the colour scheme override should be "dark"
    When I press the theme toggle
    Then the colour scheme override should be "system"
