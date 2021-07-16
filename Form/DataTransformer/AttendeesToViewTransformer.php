<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;

class AttendeesToViewTransformer extends ContextsToViewTransformer
{
    /** @var AttendeeManager */
    protected $attendeeManager;

    public function __construct(
        EntityManager $entityManager,
        AttendeeManager $attendeeManager
    ) {
        parent::__construct($entityManager);

        $this->attendeeManager = $attendeeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $entities = parent::reverseTransform($value);

        if (!$entities) {
            return $entities;
        }

        $attendees = [];
        foreach ($entities as $entity) {
            if ($entity instanceof Attendee) {
                // Entity represents existing Attendee.
                $attendee = $entity;
            } else {
                // Entity represents related entity of Attendee, such as User.
                $attendee = $this->attendeeManager->createAttendee($entity);
            }
            $attendees[] = $attendee;
        }

        $targets = explode($this->separator, $value);
        foreach ($targets as $target) {
            $target = json_decode($target, true);

            if (array_key_exists('entityClass', $target) == true && array_key_exists('id', $target) == true) {
                // Record was handled in parent method and already added to $attendees.
                continue;
            }

            if (array_key_exists('displayName', $target) && array_key_exists('email', $target)) {
                // Record has no related entity, a new instance of Attendee should be created.
                $attendee = $this->attendeeManager->createAttendee();
                $attendee->setEmail($target['email'])
                    ->setDisplayName($target['displayName']);
                $attendees[] = $attendee;
            }
        }

        return $attendees;
    }
}
