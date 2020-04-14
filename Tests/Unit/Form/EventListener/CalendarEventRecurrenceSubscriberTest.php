<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventRecurrenceSubscriber;
use Symfony\Component\Form\FormEvent;

class CalendarEventRecurrenceSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var  CalendarEventRecurrenceSubscriber
     */
    protected $calendarEventRecurrenceSubscriber;

    protected function setUp(): void
    {
        $this->calendarEventRecurrenceSubscriber = new CalendarEventRecurrenceSubscriber();
    }

    public function testPreSubmitShouldRemoveRecurrence()
    {
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $recurrenceForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $recurrence = new Recurrence();

        $event = new FormEvent($form, ['id' => 1, 'recurrence' => []]);
        $form->expects($this->any())
            ->method('has')
            ->withConsecutive(
                ['recurrence']
            )
            ->willReturn(true);
        $form->expects($this->any())
            ->method('get')
            ->with('recurrence')
            ->will($this->returnValue($recurrenceForm));
        $recurrenceForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($recurrence));
        $recurrenceForm->expects($this->once())
            ->method('setData')
            ->with(null);

        $this->calendarEventRecurrenceSubscriber->preSubmit($event);
        $this->assertEquals(['id' => 1], $event->getData());
    }
}
