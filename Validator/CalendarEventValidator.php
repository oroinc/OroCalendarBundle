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
     *
     * @param CalendarEvent $value
     * @param Constraints\CalendarEvent $constraint
     */
    public function validateCalendarEvent(CalendarEvent $value, Constraints\CalendarEvent $constraint)
    {
        $this->validateSelfRelation($value, $constraint);
        $this->validateRecurrence($value, $constraint);
        $this->validateCalendarUid($value, $constraint);
    }

    /**
     * @param CalendarEvent $value
     * @param Constraints\CalendarEvent $constraint
     */
    protected function validateSelfRelation(CalendarEvent $value, Constraints\CalendarEvent $constraint)
    {
        if ($value->getRecurringEvent() && $value->getRecurringEvent()->getId() === $value->getId()) {
            $this->context->addViolation($constraint->selfRelationMessage);
        }
    }

    /**
     * @param CalendarEvent $value
     * @param Constraints\CalendarEvent $constraint
     */
    protected function validateRecurrence(CalendarEvent $value, Constraints\CalendarEvent $constraint)
    {
        if ($value->getRecurringEvent() && $value->getRecurringEvent()->getRecurrence() === null) {
            $this->context->addViolation($constraint->wrongRecurrenceMessage);
        }
    }

    /**
     * @param CalendarEvent $value
     * @param Constraints\CalendarEvent $constraint
     */
    protected function validateCalendarUid(CalendarEvent $value, Constraints\CalendarEvent $constraint)
    {
        $recurringEvent = $value->getRecurringEvent();
        $rootContext = $this->context->getRoot();

        if ($rootContext instanceof FormInterface && $recurringEvent) {
            $calendarId = null;
            if ($rootContext->get('calendar') && $rootContext->get('calendar')->getData()) {
                $calendarId = $rootContext->get('calendar')->getData();
            }
            $calendarAlias = Calendar::CALENDAR_ALIAS;
            if ($rootContext->get('calendarAlias') && $rootContext->get('calendarAlias')->getData()) {
                $calendarAlias = $rootContext->get('calendarAlias')->getData();
            }
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
