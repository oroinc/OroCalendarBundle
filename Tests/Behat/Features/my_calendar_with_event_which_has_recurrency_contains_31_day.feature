@regression
@ticket-BAP-18184
Feature: My calendar with event which has recurrency contains 31 day
  In order to have create event which has recurrency contains 31 day
  As Admin user
  I need to be able to see this event in my calendar with correct date

  Scenario: Create event which has recurrency contains 31 day
    Given I login as administrator
    And I go to Activities / Calendar Events
    And I click "Create Calendar event"
    When I fill "Event Form" with:
      | Start            | <DateTime:2019-02-02>         |
      | Title            | All-Day event with repeats 31 |
      | Description      | testfull desc                 |
      | All-Day Event    | true                          |
      | Repeat           | true                          |
      | Repeats          | Yearly                        |
      | YearlyRecurrence | Repeat on:March Day 31        |
    When I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see "Recurrence Yearly every 1 year on Mar 31"

  Scenario: See event in my calendar
    When I click My Calendar in user menu
    And I click "Month"
    And I go to month page by name "March"
    Then I click "Event in calendar March 31"
    And I should see "Start Mar 31"
    And I should see "End Mar 31"
    And I should see "Recurrence Yearly every 1 year on Mar 31"
