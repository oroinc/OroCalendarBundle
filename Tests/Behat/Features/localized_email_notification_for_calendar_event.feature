@regression
@ticket-BAP-17336
@fixture-OroUserBundle:users.yml
@fixture-OroUserBundle:UserLocalizations.yml

Feature: Localized email notification for calendar event
  As a user
  I need to receive calendar events emails in predefined language

  Scenario: Prepare configuration with different languages
    Given sessions active:
      | Admin | first_session |
    When I proceed as the Admin
    And I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), German Localization, French Localization] |
      | Default Localization  | English (United States)                                             |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Prepare email templates for calendar invitation for different languages
    Given I go to System / Emails / Templates
    When I filter Template Name as is equal to "calendar_invitation_invite"
    And I click "edit" on first row in grid
    And fill "Email Template Form" with:
      | Subject | English Calendar Invitation Invite Subject |
      | Content | English Calendar Invitation Invite Body    |
    And I click "French"
    And fill "Email Template Form" with:
      | Subject Fallback | false                                     |
      | Content Fallback | false                                     |
      | Subject          | French Calendar Invitation Invite Subject |
      | Content          | French Calendar Invitation Invite Body    |
    And I click "German"
    And fill "Email Template Form" with:
      | Subject Fallback | false                                     |
      | Content Fallback | false                                     |
      | Subject          | German Calendar Invitation Invite Subject |
      | Content          | German Calendar Invitation Invite Body    |
    And I submit form
    Then I should see "Template saved" flash message

  Scenario: Set appropriate language setting for users
    Given I click My Configuration in user menu
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | German Localization |
    And I submit form
    Then I should see "Configuration saved" flash message
    When I go to System / User Management / Users
    And I click configuration "Charlie" in grid
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And uncheck "Use Organization" for "Default Localization" field
    And I fill form with:
      | Default Localization | French Localization |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Every User-guest of calendar event should get an invitation email in a lang of his config
    Given go to Activities/ Calendar Events
    When click "Create Calendar event"
    And I fill "Event Form" with:
      | Title  | Some Calendar event         |
      | Start  | <DateTime:2018-09-01 18:00> |
      | End    | <DateTime:2020-02-26 18:00> |
      | Guests | [Charlie Sheen, Megan Fox   |
    And I save and close form
    And click "Notify"
    Then I should see "Calendar event saved" flash message
    And Email should contains the following:
      | To      | charlie@example.com                       |
      | Subject | French Calendar Invitation Invite Subject |
      | Body    | French Calendar Invitation Invite Body    |
    And Email should contains the following:
      | To      | megan@example.com                          |
      | Subject | English Calendar Invitation Invite Subject |
      | Body    | English Calendar Invitation Invite Body    |
