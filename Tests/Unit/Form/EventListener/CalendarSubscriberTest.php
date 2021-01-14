<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarSubscriber;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CalendarSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarSubscriber */
    protected $calendarSubscriber;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarSubscriber = new CalendarSubscriber($this->tokenAccessor, $this->registry);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA  => 'fillCalendar',
            ],
            $this->calendarSubscriber->getSubscribedEvents()
        );
    }

    public function testFillCalendarIfNewEvent()
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $formData = [];
        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->will($this->returnValue(1));
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->will($this->returnValue($defaultCalendar));

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertNotNull($event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfUpdateEvent()
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $eventData->setId(2);
        $eventData->setCalendar($defaultCalendar);
        $formData = [];
        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->will($this->returnValue(1));
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->will($this->returnValue($newCalendar));

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertEquals($defaultCalendar, $event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfFilledCalendar()
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $eventData->setCalendar($defaultCalendar);
        $formData = [];
        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->will($this->returnValue(1));
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->will($this->returnValue($newCalendar));

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertEquals($defaultCalendar, $event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfSystemCalendar()
    {
        $event = $this->getMockBuilder(CalendarEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSystemCalendar')
            ->willReturn(new SystemCalendar());
        $event->expects($this->never())
            ->method('setCalendar');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $event = new FormEvent($form, $event);
        $this->calendarSubscriber->fillCalendar($event);
    }
}
