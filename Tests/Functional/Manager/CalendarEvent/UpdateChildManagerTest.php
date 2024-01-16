<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventWithReminderData;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class UpdateChildManagerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCalendarEventWithReminderData::class]);
    }

    public function testCreateWithGuestsAndReminder()
    {
        /** @var CalendarEvent $event */
        $event = $this->getReference(LoadCalendarEventWithReminderData::EVENT_REFERENCE);
        $reminder = $event->getReminders()->first();
        $organization = $event->getCalendar()->getOrganization();

        self::getContainer()->get('oro_calendar.calendar_event.update_child_manager')
            ->onEventUpdate($event, new CalendarEvent(), $organization);

        self::assertCount(\count(LoadCalendarEventWithReminderData::USER_REFERENCES), $event->getChildEvents());

        /** @var CalendarEvent $childEvent */
        foreach ($event->getChildEvents() as $childEvent) {
            self::assertCount(1, $childEvent->getReminders());
            /** @var Reminder $childReminder */
            $childReminder = $childEvent->getReminders()->first();
            self::assertInstanceOf(Reminder::class, $childReminder);
            self::assertEquals($reminder->getMethod(), $childReminder->getMethod());
            self::assertEquals($reminder->getState(), $childReminder->getState());
            self::assertEquals($reminder->getInterval(), $childReminder->getInterval());
        }
    }
}
