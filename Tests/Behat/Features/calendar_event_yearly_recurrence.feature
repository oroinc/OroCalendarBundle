@ticket-BAP-18184
@regression
@skip
# skipped because of "Select option with value|text "(UTC +00:00) Atlantic/Canary" not found" due to DST
Feature: Calendar event yearly recurrence
  In order to create calendar event with repeats
  As a user
  I need to see right recurrence value on calendar event view page

  Scenario: Configure default time zone
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | (UTC +00:00) Atlantic/Canary |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create calendar event with All-Day event and yearly repeats
    And I go to Activities / Calendar Events
    And I click "Create Calendar event"
    When I fill "Event Form" with:
      | Title                | All-Day event with repeats |
      | All-Day Event        | true                       |
      | Repeat               | true                       |
      | Repeats              | Yearly                     |
      | YearlyRecurrence     | Repeat on:January Day 8    |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see "Recurrence Yearly every 1 year on Jan 8"

  Scenario: Calendar event recurrence date will no change after system timezone update
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | (UTC -11:00) Pacific/Midway |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to Activities / Calendar Events
    And click view All-Day event with repeats in grid
    Then I should see "Recurrence Yearly every 1 year on Jan 8(Atlantic/Canary GMT+00:00)"
