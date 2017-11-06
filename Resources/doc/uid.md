UID
===

Table of Content
----------------
- [Overview](#overview)
- [Rules](#rules)

## Overview

UID is a unique identifier withing the [calendar](./systemCalendars.md). It is used to easily identify the same events
within different systems and applications (e.g. OroPlatform and MS Outlook). Please, find more information in the 
official documentation: [specification](https://icalendar.org/iCalendar-RFC-5545/3-8-4-7-unique-identifier.html)
and [update](https://icalendar.org/New-Properties-for-iCalendar-RFC-7986/5-3-uid-property.html)

## Rules

While using the UID in OroPlatform, please keep in mind the following requirements and guidelines:
 1) UID is unique withing the calendar for the parent event.
 2) A new CalendarEvent that has the $parent defined should have the same UID as its parent.
 3) A new CalendarEvent with the $recurringEvent set should have the same UID as its recurring event.
 4) Do not change the UID of the event if the event UID was already generated.
 5) While setting the UID for the first time for an existing event (when a UID is null initially), ensure that all parent, children, and recurring events obtain the same UID too.
