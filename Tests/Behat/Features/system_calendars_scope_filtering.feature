@ticket-BB-16416
@regression
@fixture-OroCalendarBundle:SystemAndOrganizationCalendarsFixture.yml
Feature: System calendars scope filtering
  In order to manage system calendars
  As a administrator
  I need to be able filter calendar datagrid by scope

  Scenario:
    Given I login as administrator
    When I go to System / System Calendars
    Then I should see "System Calendars Grid" grid
    And records in "System Calendars Grid" should be 8
    When I check "Organization" in Scope filter
    Then records in "System Calendars Grid" should be 5
    Then I should see following "System Calendars Grid" grid:
      | Scope        |
      | Organization |
      | Organization |
      | Organization |
      | Organization |
      | Organization |
    When I check "System" in Scope filter
    Then records in "System Calendars Grid" should be 3
    Then I should see following "System Calendars Grid" grid:
      | Scope  |
      | System |
      | System |
      | System |
    When I check "All" in Scope filter
    And records in "System Calendars Grid" should be 8
