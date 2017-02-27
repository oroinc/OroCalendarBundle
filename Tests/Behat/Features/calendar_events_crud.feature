Feature: User
  In order to create users
  As Admin user
  I need to be able to open Create User dialog and create new user

  Scenario: Create calendar event
    Given I login as administrator
    And go to Activities/ Calendar Events
    And press "Create Calendar event"
#    When I save and close form
#    Then I should see validation errors:
#      | Title       | This value should not be blank.  |
    When I fill form with:
      | Title          | userName       |
      | Start          | 2017-01-24 9:00 PM  |
      | End            | 2017-02-26 10:00 PM |
      | All-Day Event  | true             |
      | Description    | testfull desc    |
      | Guests         | John Doe         |
#    And I click "#5484ED"
#    And set Reminders with:
#      | Method        | Interval unit | Interval number |
#      | Email         | days          | 1               |
#      | Flash message | minutes       | 30              |
    And I save and close form
    Then I should see "Calendar event saved" flash message
#    And should see "userName" event in activity list
