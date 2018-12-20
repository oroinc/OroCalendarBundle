@ticket-CRM-6425
@automatically-ticket-tagged
Feature: User menu
  In order to have some quick links related to current user
  As an OroPlatform user
  I need to user navigation menu

  Scenario: My Calendar
    Given I login as administrator
    Given I click My Calendar in user menu
    Then I should be on Default Calendar View page
