<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\MatchingEventsManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UpdateManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateAttendeeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateChildManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateExceptionManager;

    /**
     * @var MatchingEventsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matchingEventsManager;

    /**
     * @var UpdateManager
     */
    protected $updateManager;

    protected function setUp()
    {
        $this->updateAttendeeManager = $this->getMockBuilder(
            'Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateAttendeeManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateChildManager = $this->getMockBuilder(
            'Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateChildManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateExceptionManager = $this->getMockBuilder(
            'Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateExceptionManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->matchingEventsManager = $this->getMockBuilder(MatchingEventsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateManager = new UpdateManager(
            $this->updateAttendeeManager,
            $this->updateChildManager,
            $this->updateExceptionManager,
            $this->matchingEventsManager
        );
    }

    public function testOnEventUpdate()
    {
        $entity = new CalendarEvent();
        $entity->setTitle('New Title');

        $originalEntity = clone $entity;
        $originalEntity->setTitle('Original Title test');

        $organization = new Organization();

        $allowUpdateExceptions = true;

        $this->matchingEventsManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($entity);

        $this->updateAttendeeManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($entity, $organization);

        $this->updateChildManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($entity, $originalEntity, $organization);

        $this->updateExceptionManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($entity, $originalEntity);

        $this->updateManager->onEventUpdate($entity, $originalEntity, $organization, $allowUpdateExceptions);
    }
}
