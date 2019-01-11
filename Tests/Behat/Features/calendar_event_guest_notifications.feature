@ticket-CRM-8343
@regression
@fixture-OroUserBundle:users.yml
Feature: Calendar event guest notifications
  In order to notify all guest users about new or updated calendar event
  As a User
  I need to be able to chose in popup should email notification being sent or not

  Scenario: Create new calendar event and check that notifications are not sent if Don't notify is chosen in popup
    Given I login as administrator
    And go to Activities / Calendar Events
    And I click "Create Calendar event"
    When I fill form with:
      | Title  | CalendarEvent1  |
      | Guests | [Charlie Sheen] |
    And I save form
    Then I should see "Do you want to send invitations to selected guests?"
    When I click "Don't notify"
    Then I should see "Calendar event saved" flash message
    And email with Subject "Invitation: CalendarEvent2" was not sent

  Scenario: Update created calendar event and check that notifications are not sent for new guests if Don't notify is chosen in popup
    When I fill form with:
      | Guests | [Megan Fox] |
    And I save form
    Then I should see "Do you want to notify currently invited users about changes?"
    When I click "Don't notify"
    Then I should see "Calendar event saved" flash message
    And email with Subject "Invitation: CalendarEvent2" was not sent

  Scenario: Create new calendar event and check that notifications are sent if Notify is chosen in popup
    Given go to Activities / Calendar Events
    And I click "Create Calendar event"
    When I fill form with:
      | Title  | CalendarEvent2  |
      | Guests | [Charlie Sheen] |
    And I save form
    Then I should see "Do you want to send invitations to selected guests?"
    When I click "Notify"
    Then I should see "Calendar event saved" flash message
    And email with Subject "Invitation: CalendarEvent2" containing the following was sent:
      | To   | charlie@example.com                                   |
      | Body | John Doe has sent you an invitation to CalendarEvent2 |

  Scenario: Update created calendar event and check that notifications are sent for new guests if Notify is chosen in popup
    When I fill form with:
      | Guests | [Megan Fox] |
    And I save form
    Then I should see "Do you want to notify currently invited users about changes?"
    When I click "Notify"
    Then I should see "Calendar event saved" flash message
    And email with Subject "Invitation: CalendarEvent2" containing the following was sent:
      | To   | megan@example.com                                     |
      | Body | John Doe has sent you an invitation to CalendarEvent2 |
