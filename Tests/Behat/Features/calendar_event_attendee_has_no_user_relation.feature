@ticket-BAP-21421
@regression
@fixture-OroCalendarBundle:calendarEventFields.yml
@fixture-OroCalendarBundle:Attendee.yml

Feature: Calendar event attendee has no user relation
  In order to see calendar event information
  As a User
  I need to be able to see attendees list on the Calendar Event view page even if an attendee has no User relation

  Scenario: View calendar event
    Given I login as administrator
    When I go to Activities / Calendar Events
    And click view "Call to client" in grid
    Then I should not see "500 Internal Server Error"
    And I should see Calendar Event with:
     | Title  | Call to client                          |
     | Guests | Samuel Byrd (samuel.byrd_4885e@aol.com) |
