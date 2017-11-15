<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;

class EventAttendeesValidator extends ConstraintValidator
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

        if ($calendarEvent->isOrganizer() || $attendeesFromDb === $calendarEvent->getAttendees()->getValues()) {
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
            ->getManagerForClass(Attendee::class)
            ->getRepository(Attendee::class);
    }
}
