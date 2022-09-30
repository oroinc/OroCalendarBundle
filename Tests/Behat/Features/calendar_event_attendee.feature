@ticket-BAP-21510
@fixture-OroCalendarBundle:calendarEventFields.yml
@fixture-OroCalendarBundle:Attendee.yml

Feature: Calendar event attendee
  In order to see calendar event information
  As Admin user
  I need to be able to see attendees list on the Calendar Event view page even if an attendee has no User relation

  Scenario: Create new calendar event
    Given I login as administrator
    When go to Activities / Calendar Events
    And I click "Create Calendar event"
    And I fill "Event Form" with:
      | Title       | test title extra unique title |
      | Description | test description              |
      | Start       | <DateTime:today>              |
      | End         | <DateTime:today +2 month>     |
      | Guests      | [John Doe, Charlie Sheen]     |
    And I save and close form
    Then I should see "Do you want to send invitations to selected guests?"
    When I click "Don't notify"
    Then I should see "Calendar event saved" flash message
    And page has "test title extra unique title" header
    And I should see Calendar Event with:
      | Title  | test title extra unique title                 |
      | Guests | Charlie Sheen - Required John Doe - Organizer |

  Scenario: Edit calendar event and adding guest
    When I go to Activities / Calendar Events
    And filter Title as is equal to "test title extra unique title"
    And there is one record in grid
    And I click "Edit" on row "test title extra unique title" in grid
    And I fill form with:
      | Title       | test title                   |
      | Description | test description             |
      | Guests      | [Charlie Sheen, Samuel Byrd] |
    And I save and close form
    Then I should see "Do you want to notify currently invited users about changes?"
    When I click "Don't notify"
    Then I should see "Calendar event saved" flash message
    And page has "test title" header
    And I should see Calendar Event with:
      | Title  | test title                                      |
      | Guests | Charlie Sheen - Required Samuel Byrd - Required |
