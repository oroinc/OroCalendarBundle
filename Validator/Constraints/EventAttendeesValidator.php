<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that an attendees list cannot be changed by non organizer of a calendar event.
 */
class EventAttendeesValidator extends ConstraintValidator
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     * @param CalendarEvent $calendarEvent
     */
    public function validate($calendarEvent, Constraint $constraint)
    {
        if ($calendarEvent->getId() === null) {
            return;
        }
        // fetch directly from db, not from Doctrine's proxy or already persisted entity
        /** @var CalendarEvent $eventFromDb */
        $attendeesFromDb = $this->getRepository()->getAttendeesForCalendarEvent($calendarEvent);
        usort($attendeesFromDb, [$this, 'sortAttendees']);
        $eventAttendees = $calendarEvent->getAttendees()->getValues();
        usort($eventAttendees, [$this, 'sortAttendees']);

        if ($calendarEvent->isOrganizer() !== false || $attendeesFromDb === $eventAttendees) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('attendees')
            ->addViolation();
    }

    private function sortAttendees(AttendeeEntity $attendee1, AttendeeEntity $attendee2): int
    {
        return strcmp($attendee1->getEmail(), $attendee2->getEmail());
    }

    private function getRepository(): AttendeeRepository
    {
        return $this->doctrine->getRepository(AttendeeEntity::class);
    }
}
