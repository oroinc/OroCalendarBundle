<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateChildManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UpdateChildManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UpdateChildManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->manager = new UpdateChildManager($this->doctrine);
    }

    public function testChildEventsAreNotCreatedWhenCalendarEventIsMainEventButNoOrganizer()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setIsOrganizer(false)
            ->addAttendee(new Attendee());

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->manager->onEventUpdate($calendarEvent, new CalendarEvent(), new Organization());
    }
}
