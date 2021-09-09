@ticket-BAP-18184
@regression
Feature: Calendar event yearly recurrence
  In order to create calendar event with repeats
  As a user
  I need to see right recurrence value on calendar event view/update page when select any timezone

  Scenario: Configure default time zone
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | (UTC +00:00) Other/UTC |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Create calendar event with All-Day event and yearly repeats
    And I go to Activities / Calendar Events
    And I click "Create Calendar event"
    When I fill "Event Form" with:
      | Start            | <DateTime:2018-04-02>      |
      | Title            | All-Day event with repeats |
      | All-Day Event    | true                       |
      | Repeat           | true                       |
      | Repeats          | Yearly                     |
      | YearlyRecurrence | Repeat on:January Day 8    |
    # Check that start date don't affect on the summary text
    Then I should see "Summary: Yearly every 1 year on Jan 8"
    When I fill "Event Form" with:
      | All-Day Event | false                 |
      | Start         | <DateTime:2018-04-01> |
    Then I should see "Summary: Yearly every 1 year on Jan 8"
    When I fill "Event Form" with:
      | All-Day Event | true |
    Then I should see "Summary: Yearly every 1 year on Jan 8"

    When I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see "Recurrence Yearly every 1 year on Jan 8"

  Scenario: Calendar event recurrence date will no change after system timezone update (TimeZone -11)
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | (UTC -11:00) Pacific/Midway |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to Activities / Calendar Events
    And click view All-Day event with repeats in grid
    Then I should see "Recurrence Yearly every 1 year on Jan 8"
    When I click "Edit Calendar event"
    Then I should see "Yearly every 1 year on Jan 8"
    And I save and close form

  Scenario: Calendar event recurrence date will no change after system timezone update (TimeZone +14)
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    When I fill form with:
      | Timezone | (UTC +14:00) Pacific/Kiritimati |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to Activities / Calendar Events
    And click view All-Day event with repeats in grid
    Then I should see "Recurrence Yearly every 1 year on Jan 8"
    When I click "Edit Calendar event"
    Then I should see "Yearly every 1 year on Jan 8"
    And I save and close form

  Scenario: Calendar event with All-Day event and yearly repeats and with date which has 31 day
    And I go to Activities / Calendar Events
    And I click "Create Calendar event"
    When I fill "Event Form" with:
      | Start            | <DateTime:2018-04-02>         |
      | Title            | All-Day event with repeats 31 |
      | All-Day Event    | true                          |
      | Repeat           | true                          |
      | Repeats          | Yearly                        |
      | YearlyRecurrence | Repeat on:January Day 31      |

    When I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see "Recurrence Yearly every 1 year on Jan 31"
