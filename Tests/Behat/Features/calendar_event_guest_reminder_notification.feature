@regression
@behat-test-env
@ticket-CRM-8145
@fixture-OroUserBundle:users.yml
Feature: Calendar event guest reminder notification
  In order to send reminders to all guest users about upcoming calendar event

  Scenario: Create calendar event with guests and reminder
    Given I login as administrator
    When I go to Activities / Calendar Events
    And I click "Create Calendar event"
    And I fill form with:
      | Start  | <Date:today +2 days> |
      | End    | <Date:today +4 days> |
      | Title  | CalendarEvent3     |
      | Guests | [Charlie Sheen, Megan Fox]  |
    And I set Reminders with:
      | Method | Interval unit | Interval number |
      | Email  | days          | 1               |
    And I save form
    And I should see "Do you want to send invitations to selected guests?"
    And I click "Don't notify"
    And I should see "Calendar event saved" flash message

    When I send all reminders notifications
    Then Email should contains the following:
      | Subject | CalendarEvent3 is starting |
      | To      | charlie@example.com        |
    And Email should contains the following:
      | Subject | CalendarEvent3 is starting |
      | To      | megan@example.com          |
    And Email should contains the following:
      | Subject | CalendarEvent3 is starting |
      | To      | admin@example.com          |
