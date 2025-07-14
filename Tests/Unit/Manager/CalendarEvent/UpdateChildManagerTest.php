<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager\CalendarEvent;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateChildManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateChildManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private UpdateChildManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->manager = new UpdateChildManager($this->doctrine);
    }

    public function testChildEventsAreNotCreatedWhenCalendarEventIsMainEventButNoOrganizer(): void
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setIsOrganizer(false)
            ->addAttendee(new Attendee());

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->manager->onEventUpdate($calendarEvent, new CalendarEvent(), new Organization());
    }
}
