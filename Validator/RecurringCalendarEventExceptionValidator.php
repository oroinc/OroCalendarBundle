<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventExceptionConstraint;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Responsible to validate cases related to exception of recurring calendar event.
 */
class RecurringCalendarEventExceptionValidator extends ConstraintValidator
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    public function __construct(CalendarEventManager $calendarEventManager)
    {
        $this->calendarEventManager = $calendarEventManager;
    }

    /**
     * @param CalendarEvent $value
     * @param RecurringCalendarEventExceptionConstraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $this->validateCalendarEvent($value, $constraint);
    }

    public function validateCalendarEvent(
        CalendarEvent $value,
        RecurringCalendarEventExceptionConstraint
        $constraint
    ) {
        $this->validateSelfRelation($value, $constraint);
        $this->validateRecurrence($value, $constraint);
        $this->validateCalendarUid($value, $constraint);
    }

    protected function validateSelfRelation(
        CalendarEvent $value,
        RecurringCalendarEventExceptionConstraint $constraint
    ) {
        if ($value->getRecurringEvent() && $value->getRecurringEvent()->getId() === $value->getId()) {
            $this->context->addViolation($constraint->selfRelationMessage);
        }
    }

    protected function validateRecurrence(
        CalendarEvent $value,
        RecurringCalendarEventExceptionConstraint $constraint
    ) {
        if ($value->getRecurringEvent() && $value->getRecurringEvent()->getRecurrence() === null
            && $value->getRecurringEvent()->getParent() === null) {
            $this->context->addViolation($constraint->wrongRecurrenceMessage);
        }
    }

    /**
     * This method restricts changing calendar type related to the recurring event exception.
     * For example if the exception event was created in user's calendar it is restricted to change the calendar type
     * to system or public.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validateCalendarUid(
        CalendarEvent $value,
        RecurringCalendarEventExceptionConstraint $constraint
    ) {
        $recurringEvent = $value->getRecurringEvent();
        $rootContext = $this->context->getRoot();

        if ($rootContext instanceof FormInterface && $recurringEvent && $rootContext->has('calendar')) {
            $calendarId = null;
            if ($rootContext->get('calendar') && $rootContext->get('calendar')->getData()) {
                $calendarData = $rootContext->get('calendar')->getData();
                /**
                 * 'calendar' could be an integer or CalendarEntity
                 * @see \Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType::defineCalendar
                 * @see \Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType::buildForm
                 */
                $calendarId = $calendarData instanceof Calendar ? $calendarData->getId() : $calendarData;
            }
            $calendarAlias = Calendar::CALENDAR_ALIAS;
            if ($rootContext->has('calendarAlias') &&
                $rootContext->get('calendarAlias') &&
                $rootContext->get('calendarAlias')->getData()
            ) {
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
