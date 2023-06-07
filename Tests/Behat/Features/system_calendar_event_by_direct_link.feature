@ticket-CRM-9408
@fixture-OroCalendarBundle:SystemCalendarEvent.yml
@fixture-OroUserBundle:manager.yml

Feature: System calendar event by direct link
In order to keep system ACL protected
As an Administrator
I should be sure that access to the system calendar event by direct links are ACL protected

  Scenario: Feature Background
    Given sessions active:
      | Admin  |first_session  |
      | Admin1 |second_session |

  Scenario: View calendar event with default permissions
    Given I proceed as the Admin
    Given I login as administrator
    When I go to System / System Calendars
    And I click view "System Calendar" in grid
    Then I should see "System calendars"
    And I should see "System Calendar"
    When I click view "System Calendar Event" in grid
    Then I should see "System Calendar Event"


  Scenario: Edit manage system calendar capability
    Given I proceed as the Admin1
    And I login as administrator
    And I go to System / User Management / Roles
    And I filter Label as is equal to "Administrator"
    When I click edit "Administrator" in grid
    And I uncheck "Manage System Calendars (And Their Events)" entity permission
    And save and close form
    Then I should see "Role saved" flash message

  Scenario: View calendar event by direct link without permissions
    Given I proceed as the Admin
    When I reload the page
    Then I should see "403. Forbidden You don't have permission to access this page."
