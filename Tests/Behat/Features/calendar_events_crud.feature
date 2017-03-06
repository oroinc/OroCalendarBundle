Feature: User
  In order to create users
  As Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Create AllDay No-repeat calendar event
    Given I login as administrator
    And go to Activities/ Calendar Events
    And press "Create Calendar event"
    When I save and close form
    Then I should see validation errors:
      | Title | This value should not be blank. |
    When I fill form with:
      | Title         | All day no repeat Event|
      | Start         | 2017-01-24 12:00 AM    |
      | End           | 2020-02-26 12:00 AM    |
      | All-Day Event | true                   |
      | Description   | testfull desc          |
      | Guests        | John Doe               |
    And I click "#5484ED"
    And set Reminders with:
      | Method        | Interval unit | Interval number |
      | Email         | days          | 1               |
      | Flash message | minutes       | 30              |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "All day no repeat Event" in calendar with:
      | Description   | testfull desc                |
      | Guests        | John Doe (admin@example.com) |
      | All-day event | Yes                          |
    And I press "close"

  Scenario: Create daily weekday never ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Daily weekday never ending Event |
      | Start       | today                            |
      | End         | next month                       |
      | Description | testfull desc                    |
    And set event repeating:
      | Repeats         | Daily                |
      | DailyRecurrence | Repeat every:Weekday |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "Daily weekday never ending Event" in calendar with:
      | Description   | testfull desc        |
      | All-day event | No                   |
      | Recurrence    | Daily, every weekday |
    And I press "close"

  Scenario: Create Daily every 3 days, after 5 occurrences ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Three days five occ ending Event |
      | Start       | today                                        |
      | End         | next month                                   |
      | Description | every 3 days                                 |
    And set event repeating:
      | Repeats         | Daily               |
      | DailyRecurrence | Repeat every:3 days |
      | EndsRecurrence  | After:5             |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "Three days five occ ending Event" in calendar with:
      | Description   | every 3 days                                |
      | All-day event | No                                          |
      | Recurrence    | Daily every 3 days, end after 5 occurrences |
    And I press "close"

  Scenario: Create Daily every 5 days, by next month ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Two days by month ending Event |
      | Start       | today                                      |
      | End         | next week                                  |
      | Description | every 5 days                               |
    And set event repeating:
      | Repeats         | Daily               |
      | DailyRecurrence | Repeat every:5 days |
      | EndsRecurrence  | By:next month       |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "Two days by month ending Event" in calendar with:
      | Description   | every 5 days                            |
      | All-day event | No                                      |
      | Recurrence    | Daily every 5 days, end by <next month> |
    And I press "close"

  Scenario: Create weekly, every 2 weeks on Monday, 2 occ ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Two weeks two occ ending Event |
      | Start       | today                                       |
      | End         | next month                                  |
      | Description | every 2 weeks on mondays                    |
    And set event repeating:
      | Repeats          | Weekly                                 |
      | WeeklyRecurrence | Repeat every:2 weeks, Repeat on:monday |
      | EndsRecurrence   | After:2                                |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Three weeks never ending Event with:
      | Description   | every 2 weeks on mondays                                       |
      | All-day event | No                                                             |
      | Recurrence    | Weekly every 2 weeks on Monday, end after 2 occurrences        |

  Scenario: Create weekly, every 3 weeks never ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Three weeks never ending Event |
      | Start       | today                          |
      | End         | next month                     |
      | Description | every 3 weeks                  |
    And set event repeating:
      | Repeats          | Weekly                                 |
      | WeeklyRecurrence | Repeat every:3 weeks, Repeat on:sunday |
      | EndsRecurrence   | Never                                  |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Three weeks never ending Event with:
      | Description   | every 3 weeks                                                  |
      | All-day event | No                                                             |
      | Recurrence    | Weekly every 3 weeks on Sunday |

  Scenario: Create Monthly First Weekday next year ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Monthly First Weekday by ny ending Event |
      | Start       | today                                    |
      | End         | next month                               |
      | Description | every First Weekday of month             |
    And set event repeating:
      | Repeats           | Monthly                 |
      | MonthlyRecurrence | Repeat on:First Weekday |
      | EndsRecurrence    | By:next year            |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Monthly First Weekday by ny ending Event with:
      | Description   | every First Weekday of month                                   |
      | All-day event | No                                                             |
      | Recurrence    | Monthly the first weekday of every 1 month, end by <next year> |

  Scenario: Create yearly April Day 1, 5 recurrence ending Event
    When I go to Activities/ Calendar Events
    And press "Create Calendar event"
    Then I fill form with:
      | Title       | Yearly April Day one rcr five ending Event  |
      | Start       | today                                       |
      | End         | next year                                   |
      | Description | Yearly April Day one recurrence five ending |
    And set event repeating:
      | Repeats          | Yearly                    |
      | YearlyRecurrence | Repeat on:April First Day |
      | EndsRecurrence   | After:5                   |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Yearly April Day one rcr five ending Event with:
      | Description   | Yearly April Day one recurrence five ending                          |
      | All-day event | No                                                                   |
      | Recurrence    | Yearly every 1 year on the first day of Apr, end after 5 occurrences |
