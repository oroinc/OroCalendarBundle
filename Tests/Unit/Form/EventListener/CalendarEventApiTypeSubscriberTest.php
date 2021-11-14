<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class CalendarEventApiTypeSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    /** @var CalendarEventApiTypeSubscriber */
    private $calendarEventApiTypeSubscriber;

    protected function setUp(): void
    {
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);

        $this->calendarEventApiTypeSubscriber = new CalendarEventApiTypeSubscriber(
            $this->calendarEventManager
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

    public function testPostSubmitDataShouldNotSetCalendarWhenNoCalendarEvent()
    {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->calendarEventManager->expects($this->never())
            ->method('setCalendar');

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldNotSetCalendarWhenNoCalendar()
    {
        $calendarEvent = new CalendarEvent();
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $calendarEvent);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn($calendarEvent);

        $calendarForm = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('calendar')
            ->willReturn($calendarForm);
        $calendarForm->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->calendarEventManager->expects($this->never())
            ->method('setCalendar');

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    /**
     * @dataProvider testPostSubmitDataShouldSetCalendarProvider
     */
    public function testPostSubmitDataShouldSetCalendar(
        CalendarEvent $calendarEvent,
        int $calendarId,
        ?string $alias,
        string $expectedAlias
    ) {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $calendarEvent);

        $calendarForm = $this->createMock(FormInterface::class);
        $calendarForm->expects($this->once())
            ->method('getData')
            ->willReturn($calendarId);

        $calendarAliasForm = $this->createMock(FormInterface::class);
        $calendarAliasForm->expects($this->once())
            ->method('getData')
            ->willReturn($alias);

        $form->expects($this->once())
            ->method('getData')
            ->willReturn($calendarEvent);
        $form->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['calendar', $calendarForm],
                ['calendarAlias', $calendarAliasForm],
            ]);

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($calendarEvent, $expectedAlias, $calendarId);

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldSetCalendarProvider(): array
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
