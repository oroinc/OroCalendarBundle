<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates cases related to exception of recurring calendar event.
 */
class RecurringCalendarEventExceptionValidator extends ConstraintValidator
{
    private CalendarEventManager $calendarEventManager;

    public function __construct(CalendarEventManager $calendarEventManager)
    {
        $this->calendarEventManager = $calendarEventManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof RecurringCalendarEventException) {
            throw new UnexpectedTypeException($constraint, RecurringCalendarEventException::class);
        }
        if (!$value instanceof CalendarEvent) {
            throw new UnexpectedTypeException($value, CalendarEvent::class);
        }

        $this->validateSelfRelation($value, $constraint);
        $this->validateRecurrence($value, $constraint);
        $this->validateCalendarUid($value, $constraint);
    }

    private function validateSelfRelation(
        CalendarEvent $value,
        RecurringCalendarEventException $constraint
    ) {
        if ($value->getRecurringEvent() && $value->getRecurringEvent()->getId() === $value->getId()) {
            $this->context->addViolation($constraint->selfRelationMessage);
        }
    }

    private function validateRecurrence(
        CalendarEvent $value,
        RecurringCalendarEventException $constraint
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
    private function validateCalendarUid(
        CalendarEvent $value,
        RecurringCalendarEventException $constraint
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
