<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventRecurrenceSubscriber;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class CalendarEventRecurrenceSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventRecurrenceSubscriber */
    private $calendarEventRecurrenceSubscriber;

    protected function setUp(): void
    {
        $this->calendarEventRecurrenceSubscriber = new CalendarEventRecurrenceSubscriber();
    }

    public function testPreSubmitShouldRemoveRecurrence()
    {
        $form = $this->createMock(FormInterface::class);
        $recurrenceForm = $this->createMock(FormInterface::class);
        $recurrence = new Recurrence();

        $event = new FormEvent($form, ['id' => 1, 'recurrence' => []]);
        $form->expects($this->any())
            ->method('has')
            ->with('recurrence')
            ->willReturn(true);
        $form->expects($this->any())
            ->method('get')
            ->with('recurrence')
            ->willReturn($recurrenceForm);
        $recurrenceForm->expects($this->once())
            ->method('getData')
            ->willReturn($recurrence);
        $recurrenceForm->expects($this->once())
            ->method('setData')
            ->with(null);

        $this->calendarEventRecurrenceSubscriber->preSubmit($event);
        $this->assertEquals(['id' => 1], $event->getData());
    }
}
