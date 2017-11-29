@fixture-OroCalendarBundle:calendarEventFields.yml

Feature: Calendar Event Organizer field
  In order to have all information about calendar events
  As a user
  I need to see all defined fields on event page

  Scenario: Calendar Event with link to organizer profile
    Given I login as administrator
    And go to Activities/ Calendar Events
    And I click View "Call to client" in grid
    And I should see Call to client with:
      | Organizer | Charlie Sheen |

  Scenario: Calendar Event with organizer without user account in system
    Given go to Activities/ Calendar Events
    And I click View "Meeting" in grid
    And I should see Call to client with:
      | Organizer | Charlie Sheen (charlie@example.com) |

  Scenario: Calendar Event doesn't have any organizer information's
    Given go to Activities/ Calendar Events
    And I click View "Business trip" in grid
    And I should see Call to client with:
      | Organizer | N/A |

  Scenario: Calendar Event in My Calendar with link to organizer profile
    Given I click My Calendar in user menu
    And I should see "Call to client" in calendar with:
      | Organizer | Charlie Sheen |

  Scenario: Calendar Event in My Calendar with organizer without user profile in system
    Given I should see "Meeting" in calendar with:
      | Organizer | Charlie Sheen (charlie@example.com) |

  Scenario: Calendar Event in My Calendar when event doesn't have any organizer information's
    And I should see "Business trip" in calendar with:
      | Organizer | N/A |
