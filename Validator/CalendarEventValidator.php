<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;

class CalendarEventValidator extends ConstraintValidator
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * @param CalendarEventManager $calendarEventManager
     */
    public function __construct(CalendarEventManager $calendarEventManager)
    {
        $this->calendarEventManager = $calendarEventManager;
    }

    /**
     * @param CalendarEvent $value
     *
     * @param Constraints\CalendarEvent $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $this->validateCalendarEvent($value, $constraint);
    }

    /**
     * @param CalendarEvent $value
     * @param Constraints\CalendarEvent $constraint
     */
    public function validateCalendarEvent(CalendarEvent $value, Constraints\CalendarEvent $constraint)
    {
        $recurringEvent = $value->getRecurringEvent();
        $rootContext = $this->context->getRoot();
        if ($recurringEvent && $recurringEvent->getId() === $value->getId()) {
            $this->context->addViolation($constraint->selfRelationMessage);
        }

        if ($recurringEvent && $recurringEvent->getRecurrence() === null) {
            $this->context->addViolation($constraint->wrongRecurrenceMessage);
        }

        if ($rootContext instanceof FormInterface && $recurringEvent) {
            $calendarId = $rootContext->get('calendar') ?
                $rootContext->get('calendar')->getData() :
                null;
            $calendarAlias = $rootContext->get('calendarAlias') ?
                $rootContext->get('calendarAlias')->getData() :
                Calendar::CALENDAR_ALIAS;
            if ($calendarId) {
                // This is case for front-end form templates and API form. The case is used for all types of events.
                // Simple forms of any kind of events (regular, recurrent, system, system recurrent)
                // do not have "Calendar" drop-down.
                $calendarUid = $this->calendarEventManager->getCalendarUid($calendarAlias, $calendarId);
                if ($recurringEvent->getCalendarUid() !== $calendarUid) {
                    $this->context->addViolation($constraint->cantChangeCalendarMessage);
                }
            }
        }
    }
}
