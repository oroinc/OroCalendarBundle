<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EventAttendeesValidator extends ConstraintValidator
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param AttendeeEntity $attendee1
     * @param AttendeeEntity $attendee2
     * @return int
     */
    private function sortAttendees(AttendeeEntity $attendee1, AttendeeEntity $attendee2)
    {
        return strcmp($attendee1->getEmail(), $attendee2->getEmail());
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
        usort($attendeesFromDb, [$this, "sortAttendees"]);
        $eventAttendees = $calendarEvent->getAttendees()->getValues();
        usort($eventAttendees, [$this, "sortAttendees"]);

        if ($calendarEvent->isOrganizer() !== false || $attendeesFromDb === $eventAttendees) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath('attendees')
            ->addViolation();
    }

    /**
     * @return ObjectRepository|AttendeeRepository
     */
    private function getRepository(): ObjectRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(AttendeeEntity::class)
            ->getRepository(AttendeeEntity::class);
    }
}
