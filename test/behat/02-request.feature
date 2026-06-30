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

  Scenario: Query strings, headers, raw request and response are updated from editor fields
    Given I am on the homepage
    And I fill in the "request-editor" input "name" with "Full request editor coverage"
    And I fill in the "request-editor" input "method" with "POST"
    And I fill in the "request-editor" input "endpoint" with "https://example.com/api/things"
    And I submit the form

    When I press "New query parameter" in the request editor "query-string-parameter"
    And I fill in row 1 of the request editor "query-string-parameter" input "key" with "keep"
    And I fill in row 1 of the request editor "query-string-parameter" input "value" with "yes"
    And I submit the form
    And I press "New query parameter" in the request editor "query-string-parameter"
    And I fill in row 2 of the request editor "query-string-parameter" input "key" with "remove"
    And I fill in row 2 of the request editor "query-string-parameter" input "value" with "no"
    And I submit the form
    And I press "New query parameter" in the request editor "query-string-parameter"
    And I fill in row 3 of the request editor "query-string-parameter" input "key" with "page"
    And I fill in row 3 of the request editor "query-string-parameter" input "value" with "2"
    And I submit the form
    And I press "Delete" in row 2 of the request editor "query-string-parameter"
    Then I should see "Query parameters 2" in the "request-editor"

    When I press "New header" in the request editor "header"
    And I fill in row 1 of the request editor "header" input "key" with "X-Request-One"
    And I fill in row 1 of the request editor "header" input "value" with "one-value"
    And I submit the form
    And I press "New header" in the request editor "header"
    And I fill in row 2 of the request editor "header" input "key" with "X-Delete-Me"
    And I fill in row 2 of the request editor "header" input "value" with "delete-value"
    And I submit the form
    And I press "New header" in the request editor "header"
    And I fill in row 3 of the request editor "header" input "key" with "X-Request-Two"
    And I fill in row 3 of the request editor "header" input "value" with "two-value"
    And I submit the form
    And I press "Delete" in row 2 of the request editor "header"
    Then I should see "Headers 2" in the "request-editor"

    When I fill in the request editor "body" input "body-type" with "text"
    And I press "Set body type" in the request editor "body"
    And I fill in the request editor "body" input "body-raw" with "plain request body"
    And I submit the form
    Then the request raw message should be:
      """
      POST /api/things?keep=yes&page=2 HTTP/1.1
      Host: example.com
      X-Request-One: one-value
      X-Request-Two: two-value
      Content-type: text/plain

      plain request body
      """

    When I press "Send request"
    Then I should see "Completed in"
    And I should see "Method: POST"
    And I should see "Path: /api/things"
    And I should see "Query: keep=yes&page=2"
    And I should see "Request header one: one-value"
    And I should see "Request header two: two-value"
    And I should see "Request body: plain request body"
    And I should not see "remove=no"
    And I should not see "delete-value"
