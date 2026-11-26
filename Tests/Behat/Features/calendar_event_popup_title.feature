@regression
@ticket-BAP-22814
@ticket-BB-26523
@fixture-OroUserBundle:user.yml

Feature: Calendar event popup title
  In order to see correct dialog title
  As Admin user
  I need to see "Edit Event" title (not duplicated "View Event") when switching from view to edit in calendar popup

  Scenario: Dialog title is not duplicated when switching from view to edit
    Given I login as administrator
    And I go to Activities/ Calendar Events
    And I click "Create Calendar event"
    And I fill "Event Form" with:
      | Title  | Popup Title Test Event |
      | Start  | <DateTime:today>       |
      | End    | <DateTime:today>       |
      | Guests | [Charlie Sheen]        |
    And I save and close form
    And I click "Don't notify"
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    And I click on "Calendar Event"
    Then I should see "View Event"
    When I click "Edit"
    Then I should see "Edit Event"
    And I should not see "View Event"

  Scenario: Event with guests is saved from popup without changes
    When I click "Save" in modal window
    Then I should not see "Edit Event"
    When I go to Activities/ Calendar Events
    And I click view "Popup Title Test Event" in grid
    Then I should see Calendar Event with:
      | Guests | Charlie Sheen - Required |
