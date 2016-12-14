<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;

class AttendeesToViewTransformer extends ContextsToViewTransformer
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param EntityManager $entityManager
     * @param TokenStorageInterface $securityTokenStorage
     * @param AttendeeRelationManager $attendeeRelationManager
     */
    public function __construct(
        EntityManager $entityManager,
        TokenStorageInterface $securityTokenStorage,
        AttendeeRelationManager $attendeeRelationManager
    ) {
        parent::__construct($entityManager, $securityTokenStorage);

        $this->attendeeRelationManager = $attendeeRelationManager;
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

        $attendees = array_map(
            function ($entity) {
                return $this->attendeeRelationManager->createAttendee($entity) ?: $entity;
            },
            $entities
        );

        $targets = explode(';', $value);
        foreach ($targets as $target) {
            $target = json_decode($target, true);
            if (array_key_exists('entityClass', $target) !== true &&
                array_key_exists('id', $target) !== true &&
                array_key_exists('displayName', $target) &&
                array_key_exists('email', $target)
            ) {
                $newAttendee = new Attendee();
                $newAttendee->setEmail($target['email'])
                    ->setDisplayName($target['displayName']);
                $attendees[] = $newAttendee;
            }
        }

        return $attendees;
    }
}
