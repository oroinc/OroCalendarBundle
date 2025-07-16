<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class CalendarEventApiTypeSubscriberTest extends TestCase
{
    private CalendarEventManager&MockObject $calendarEventManager;
    private CalendarEventApiTypeSubscriber $calendarEventApiTypeSubscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);

        $this->calendarEventApiTypeSubscriber = new CalendarEventApiTypeSubscriber(
            $this->calendarEventManager
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SUBMIT  => ['preSubmitData', 10],
                FormEvents::POST_SUBMIT  => ['postSubmitData', 10],
            ],
            CalendarEventApiTypeSubscriber::getSubscribedEvents()
        );
    }

    public function testPostSubmitDataShouldNotSetCalendarWhenNoCalendarEvent(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $form->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->calendarEventManager->expects(self::never())
            ->method('setCalendar');

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldNotSetCalendarWhenNoCalendar(): void
    {
        $calendarEvent = new CalendarEvent();
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $calendarEvent);

        $form->expects(self::once())
            ->method('getData')
            ->willReturn($calendarEvent);

        $calendarForm = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('calendar')
            ->willReturn($calendarForm);
        $calendarForm->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->calendarEventManager->expects(self::never())
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
    ): void {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $calendarEvent);

        $calendarForm = $this->createMock(FormInterface::class);
        $calendarForm->expects(self::once())
            ->method('getData')
            ->willReturn($calendarId);

        $calendarAliasForm = $this->createMock(FormInterface::class);
        $calendarAliasForm->expects(self::once())
            ->method('getData')
            ->willReturn($alias);

        $form->expects(self::once())
            ->method('getData')
            ->willReturn($calendarEvent);
        $form->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['calendar', $calendarForm],
                ['calendarAlias', $calendarAliasForm],
            ]);

        $this->calendarEventManager->expects(self::once())
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

    /**
     * @dataProvider formDataProvider
     */
    public function testPreSubmitShouldFixFields(array $expected, array $data): void
    {
        $form = $this->createMock(FormInterface::class);

        $event = new FormEvent($form, $data);
        $this->calendarEventApiTypeSubscriber->preSubmitData($event);
        self::assertSame($expected, $event->getData());
    }

    public function formDataProvider(): iterable
    {
        yield 'positive boolean fields' => [
            'expected' => [
                'allDay' => true,
                'isCancelled' => true,
            ],
            'data' => [
                'allDay' => '1',
                'isCancelled' => 'TRUE',
            ],
        ];

        yield 'negative boolean fields' => [
            'expected' => [
                'allDay' => false,
                'isCancelled' => false,
            ],
            'data' => [
                'allDay' => '0',
                'isCancelled' => 0,
            ],
        ];

        yield 'attendees fix' => [
            'expected' => [
                'attendees' => null,
            ],
            'data' => [
                'attendees' => '',
            ],
        ];

        yield 'updatedAt fix' => [
            'expected' => [
            ],
            'data' => [
                'updatedAt' => 'any value',
            ],
        ];
    }
}
