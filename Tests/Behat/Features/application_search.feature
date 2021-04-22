@regression
@ticket-CRM-6425
@automatically-ticket-tagged
@fixture-OroCalendarBundle:searchCalendarEvent.yml
Feature: Application search
  In order to decrease time for search some common entities
  As a user
  I need to search functionality

  Scenario: Search all
    Given I login as administrator
    And I click "Search"
    And type "Common" in "search"
    And I should see 3 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type            | N | isSelected |
      | All             | 3 | yes        |
      | Business Units  | 1 |            |
      | Calendar Events | 1 |            |
      | Organizations   | 1 |            |
    And number of records should be 3
    And I should see following search results:
      | Title                | Type           |
      | Common Organization  | Organization   |
      | Common Event         | Calendar event |
      | Common Business Unit | Business Unit  |

  Scenario: Filter result by entity type
    Given I filter result by "Calendar" type
    Then number of records should be 1
    And I should see following search results:
      | Title                | Type           |
      | Common Event         | Calendar event |
    And should see following search entity types:
      | Type            | N | isSelected |
      | All             | 3 |            |
      | Business Units  | 1 |            |
      | Calendar Events | 1 | yes        |
      | Organizations   | 1 |            |
    When I filter result by "Calendar Events" type
    Then number of records should be 1
    And I should see following search results:
      | Title        | Type           |
      | Common Event | Calendar event |
    And should see following search entity types:
      | Type            | N | isSelected |
      | All             | 3 |            |
      | Business Units  | 1 |            |
      | Calendar Events | 1 | yes        |
      | Organizations   | 1 |            |
