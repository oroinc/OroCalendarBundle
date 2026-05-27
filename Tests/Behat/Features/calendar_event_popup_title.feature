@regression
@ticket-BAP-22814

Feature: Calendar event popup title
  In order to see correct dialog title
  As Admin user
  I need to see "Edit Event" title (not duplicated "View Event") when switching from view to edit in calendar popup

  Scenario: Dialog title is not duplicated when switching from view to edit
    Given I login as administrator
    And I go to Activities/ Calendar Events
    And I click "Create Calendar event"
    And I fill "Event Form" with:
      | Title | Popup Title Test Event |
      | Start | <DateTime:today>       |
      | End   | <DateTime:today>       |
    And I save and close form
    Then I should see "Calendar event saved" flash message
    When I click My Calendar in user menu
    And I click on "Calendar Event"
    Then I should see "View Event"
    When I click "Edit"
    Then I should see "Edit Event"
    And I should not see "View Event"
