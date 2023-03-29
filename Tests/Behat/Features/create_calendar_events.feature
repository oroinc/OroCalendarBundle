@regression
@ticket-BB-11878
Feature: Create calendar events
  In order to have my company events organized
  As Admin user
  I need to be able to create events with different recurrences

  Scenario: Create AllDay calendar event from calendar week view
    Given I login as administrator
    And I click My Calendar in user menu
    And I click "Week"
    And I click "First All Day Cell"
    And "Event Form" must contains values:
      | All-Day Event | true |
    Then I check start and end dates are the same for calendar event
    When I fill "Event Form" with:
      | Title | One day Event |
    And I click "Save"
    Then I should see "One day Event" in calendar with:
      | All-day event | Yes |
    And I should not see an "Multiday Event" element

  Scenario: Check Reminder errors
    When go to Activities/ Calendar Events
    And click "Create Calendar event"
    When I set Reminders with:
      | Method | Interval unit | Interval number |
      | Email  | minutes       | 21347349587354  |
    Then I should see "Event Form" validation errors:
      | Reminder 1 Interval number | This value should be between 1 and 2,147,483,647. |
    When I fill "Event Form" with:
      | Title | Event with wrong reminder |
    And I set Reminders with:
      | Method | Interval unit | Interval number |
      | Email  | days          | 999999999       |
    Then I save and close form
    And I should see "Event Form" validation errors:
      | Reminder 1 Interval number | Reminder start date less than now. |
    Then I click "Cancel"

  Scenario: Create AllDay No-repeat calendar event
    When click "Create Calendar event"
    And I check switching All-Day Event on and off doesn't change event start and end time
    When I save and close form
    Then I should see validation errors:
      | Title | This value should not be blank. |
    When I fill "Event Form" with:
      | Title         | All day no repeat Event |
      | Start         | <DateTime:-1 year>      |
      | End           | <DateTime:+1 year>      |
      | All-Day Event | true                    |
      | Description   | testfull desc           |
      | Guests        | John Doe                |
      | Color         | Cornflower Blue         |
    And I save and close form
    And click "Notify"
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "All day no repeat Event" in calendar with:
      | Description   | testfull desc                |
      | Guests        | John Doe (admin@example.com) |
      | All-day event | Yes                          |
      | Start         | <DateTime:-1 year 12:00 AM>  |
      | End           | <DateTime:+1 year 11:59 PM>  |

  Scenario: Create daily weekday never ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title           | Daily weekday never ending Event |
      | Start           | <DateTime:today>                 |
      | End             | <DateTime:today +2 month>        |
      | Description     | testfull desc                    |
      | Repeats         | Daily                            |
      | DailyRecurrence | Repeat every:Weekday             |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    And I go to next calendar page
    Then I should see "Daily weekday never ending Event" in calendar with:
      | Description   | testfull desc        |
      | All-day event | No                   |
      | Recurrence    | Daily, every weekday |

  Scenario: Create Daily every 3 days, after 5 occurrences ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title           | Three days five occ ending Event |
      | Start           | <DateTime:today>                 |
      | End             | <DateTime:next month>            |
      | Description     | every 3 days                     |
      | Repeats         | Daily                            |
      | DailyRecurrence | Repeat every:3 days              |
      | EndsRecurrence  | After:5                          |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "Three days five occ ending Event" in calendar with:
      | Description   | every 3 days                                |
      | All-day event | No                                          |
      | Recurrence    | Daily every 3 days, end after 5 occurrences |

  Scenario: Create Daily every 5 days, by next month ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title           | Two days by month ending Event |
      | Start           | <DateTime:today>               |
      | End             | <DateTime:next week>           |
      | Description     | every 5 days                   |
      | Repeats         | Daily                          |
      | DailyRecurrence | Repeat every:5 days            |
      | EndsRecurrence  | By:next month                  |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    Then I should see "Two days by month ending Event" in calendar with:
      | Description   | every 5 days                                 |
      | All-day event | No                                           |
      | Recurrence    | Daily every 5 days, end by <Date:next month> |

  Scenario: Create weekly, every 2 weeks on Monday, 2 occ ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title            | Two weeks two occ ending Event         |
      | Start            | <DateTime:today>                       |
      | End              | <DateTime:next month>                  |
      | Description      | every 2 weeks on mondays               |
      | Repeats          | Weekly                                 |
      | WeeklyRecurrence | Repeat every:2 weeks, Repeat on:monday |
      | EndsRecurrence   | After:2                                |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Three weeks never ending Event with:
      | Description   | every 2 weeks on mondays                                |
      | All-day event | No                                                      |
      | Recurrence    | Weekly every 2 weeks on Monday, end after 2 occurrences |

  Scenario: Create weekly, every 3 weeks never ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title            | Three weeks never ending Event         |
      | Start            | <DateTime:today>                       |
      | End              | <DateTime:next month>                  |
      | Description      | every 3 weeks                          |
      | Repeats          | Weekly                                 |
      | WeeklyRecurrence | Repeat every:3 weeks, Repeat on:sunday |
      | EndsRecurrence   | Never                                  |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Three weeks never ending Event with:
      | Description   | every 3 weeks                  |
      | All-day event | No                             |
      | Recurrence    | Weekly every 3 weeks on Sunday |

  Scenario: Create Monthly First Weekday next year ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title             | Monthly First Weekday by ny ending Event |
      | Start             | <DateTime:today>                         |
      | End               | <DateTime:next month>                    |
      | Description       | every First Weekday of month             |
      | Repeats           | Monthly                                  |
      | MonthlyRecurrence | Repeat on:First Weekday                  |
      | EndsRecurrence    | By:next year                             |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Monthly First Weekday by ny ending Event with:
      | Description   | every First Weekday of month                                        |
      | All-day event | No                                                                  |
      | Recurrence    | Monthly the first weekday of every 1 month, end by <Date:next year> |

  @skip
  Scenario: Create yearly April Day 1, 5 recurrence ending Event
    When I go to Activities/ Calendar Events
    And click "Create Calendar event"
    Then I fill "Event Form" with:
      | Title            | Yearly April Day one rcr five ending Event  |
      | Start            | <DateTime:today>                            |
      | End              | <DateTime:next year>                        |
      | Description      | Yearly April Day one recurrence five ending |
      | Repeats          | Yearly                                      |
      | YearlyRecurrence | Repeat on:April First Day                   |
      | EndsRecurrence   | After:5                                     |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see Yearly April Day one rcr five ending Event with:
      | Description   | Yearly April Day one recurrence five ending                          |
      | All-day event | No                                                                   |
      | Recurrence    | Yearly every 1 year on the first day of Apr, end after 5 occurrences |

  @skip
  Scenario: Edit yearly April Day 1, 5 recurrence ending Event
    When I click "Edit Calendar event"
    And I fill "Event Form" with:
      | Title            | New year Event                                |
      | Start            | <DateTime:today>                              |
      | End              | <DateTime:+3 years>                           |
      | Description      | Yearly January Day one recurrence five ending |
      | Repeats          | Yearly                                        |
      | YearlyRecurrence | Repeat on:January First Day                   |
      | EndsRecurrence   | Never                                         |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    And I should see New year Event with:
      | Description   | Yearly January Day one recurrence five ending |
      | All-day event | No                                            |
      | Recurrence    | Yearly every 1 year on the first day of Jan   |

  Scenario: Delete calendar event
    Given I click "Delete"
    When I confirm deletion
    Then I should see "Calendar event deleted" flash message
    And I should not see "New year Event"

  Scenario: Delete all events
    Given I go to Activities/ Calendar Events
    And I check all records in grid
    When I click "Delete" link from mass action dropdown
    And I confirm deletion
    Then I should see success message with number of records were deleted

  Scenario: Create recurrent calendar event with default UTC +08:00 timezone on dashboard widget
    Given I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use default" for "Timezone" field
    And I fill form with:
      | Timezone | (UTC +08:00) Asia/Taipei |
    And I click "Save settings"
    When I login as administrator
    And I scroll to "Empty slot at 10:30 PM"
    And I click on "Empty slot at 10:30 PM"
    And I fill "Event Form" with:
      | Title  | Late recurrent event |
      | Repeat | true                 |
    And I click "Save" in modal window
    Then I should see "Late recurrent event"
