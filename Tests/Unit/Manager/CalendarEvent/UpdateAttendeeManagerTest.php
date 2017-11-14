<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateAttendeeManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UpdateAttendeeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrine;

    /** @var AttendeeRelationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $attendeeRelationManager;

    /** @var UpdateAttendeeManager */
    private $manager;

    protected function setUp()
    {
        $this->attendeeRelationManager = $this->getMockBuilder(AttendeeRelationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new UpdateAttendeeManager(
            $this->attendeeRelationManager,
            $this->doctrine
        );
    }

    public function testRemoveAttendeesIfNotOrganizer()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setIsOrganizer(false)
            ->addAttendee(new Attendee());

        $this->attendeeRelationManager->expects($this->never())
            ->method('bindAttendees');

        $this->manager->onEventUpdate($calendarEvent, new Organization());

        $this->assertCount(0, $calendarEvent->getAttendees());
    }
}
