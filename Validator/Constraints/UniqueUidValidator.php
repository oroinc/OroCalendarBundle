<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Calendar;

/**
 * This validator checks if UID in CalendarEvent is unique across one calendar.
 * Exceptions of this rule:
 *    - Recurring events (and its exceptions) share the same UID
 *    - Child event shares the same UID with parent and all siblings
 */
class UniqueUidValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param FormInterface $form
     * @param CalendarEvent $calendarEvent
     *
     * @return null|int|Calendar
     */
    private function getCalendarId($form, $calendarEvent)
    {
        $calendarId = $form->has('calendar') ? $form->get('calendar')->getData(): null;
        if ($calendarId === null) {
            $calendarId = $calendarEvent->getCalendar() ? $calendarEvent->getCalendar()->getId() : null;
        }

        return $calendarId;
    }

    /**
     * {@inheritdoc}
     * @param CalendarEvent $calendarEvent
     */
    public function validate($calendarEvent, Constraint $constraint)
    {
        // do not validate in case calendar event has a parent (see class annotation) or does not have UID
        if ($calendarEvent->getUid() === null || $calendarEvent->getParent() !== null) {
            return;
        }

        //If event has recurring event, it should have the same UID
        if ($calendarEvent->getRecurringEvent() !== null
            && $calendarEvent->getUid() === $calendarEvent->getRecurringEvent()->getUid()
        ) {
            return;
        }

        // calendar field is not mapped, so we need to take value from request. See CalendarEventApiTypeSubscriber
        $calendarId = $this->getCalendarId($this->context->getRoot(), $calendarEvent);

        // if calendar is not specified it can be a system calendar's event
        if ($calendarId === null) {
            return;
        }

        $events = $this->getRepository()->findDuplicatedEvent($calendarEvent, $calendarId);

        if (count($events) > 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('uid')
                ->addViolation();
        }
    }

    /**
     * @return ObjectRepository|CalendarEventRepository
     */
    private function getRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass(CalendarEvent::class)
            ->getRepository(CalendarEvent::class);
    }
}
