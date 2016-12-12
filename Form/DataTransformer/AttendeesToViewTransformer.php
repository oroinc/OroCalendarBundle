<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

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
    public function transform($value)
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return parent::transform($value);
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

        return array_map(
            function ($entity) {
                return $this->attendeeRelationManager->createAttendee($entity) ?: $entity;
            },
            $entities
        );
    }
}
