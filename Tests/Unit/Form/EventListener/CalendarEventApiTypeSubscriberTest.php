<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class CalendarEventApiTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $calendarEventManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var CalendarEventApiTypeSubscriber */
    protected $calendarEventApiTypeSubscriber;

    protected function setUp(): void
    {
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = new RequestStack();

        $this->calendarEventApiTypeSubscriber = new CalendarEventApiTypeSubscriber(
            $this->calendarEventManager,
            $this->requestStack
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SUBMIT  => ['preSubmitData', 10],
                FormEvents::POST_SUBMIT  => ['postSubmitData', 10],
            ],
            CalendarEventApiTypeSubscriber::getSubscribedEvents()
        );
    }

    /**
     * @dataProvider testPostSubmitDataShouldNotSetCalendarProvider
     */
    public function testPostSubmitDataShouldNotSetCalendar($calendar, CalendarEvent $calendarEvent = null)
    {
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $event = new FormEvent($form, $calendarEvent);

        $calendarForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $calendarForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendar));

        $calendarAliasForm = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->any())
            ->method('get')
            ->with('calendar')
            ->will($this->returnValue($calendarForm));
        $form->expects($this->any())
            ->method('get')
            ->with('calendarAlias')
            ->will($this->returnValue($calendarAliasForm));

        $this->calendarEventManager->expects($this->never())
            ->method('setCalendar');

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldNotSetCalendarProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                null,
                new CalendarEvent(),
            ],
        ];
    }

    /**
     * @dataProvider testPostSubmitDataShouldSetCalendarProvider
     */
    public function testPostSubmitDataShouldSetCalendar(CalendarEvent $calendarEvent, $calendar, $alias, $expectedAlias)
    {
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $event = new FormEvent($form, $calendarEvent);

        $calendarForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $calendarForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendar));

        $calendarAliasForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $calendarAliasForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($alias));

        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendarEvent));
        $form->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['calendar', $calendarForm],
                ['calendarAlias', $calendarAliasForm],
            ]));

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($calendarEvent, $expectedAlias, $calendar);

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldSetCalendarProvider()
    {
        return [
            [
                new CalendarEvent(),
                1,
                'alias',
                'alias',
            ],
            [
                new CalendarEvent(),
                1,
                null,
                'user',
            ],
        ];
    }
}
