<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Query;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;

/**
 * @deprecated This validator is deprecated and will be removed in the future. Use UnchangeableFieldValidator instead
 */
class UnchangeableUidValidator extends ConstraintValidator
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
        if ($calendarEvent->getId() === null || $calendarEvent->getUid() === null) {
            return;
        }

        // fetch directly from db, not from Doctrine's proxy or already persisted entity
        $uidFromDb = $this->getRepository()->createQueryBuilder('e')
            ->select('e.uid')
            ->where('e.id = :id')->setParameter('id', $calendarEvent->getId())
                ->getQuery()->getOneOrNullResult(Query::HYDRATE_SCALAR);

        if ($uidFromDb === null || !isset($uidFromDb['uid'])) {
            return;
        }

        if ($uidFromDb['uid'] !== $calendarEvent->getUid()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('uid')
                ->addViolation();
        }
    }

    /**
     * @return ObjectRepository|CalendarEventRepository
     */
    private function getRepository(): ObjectRepository
    {
        return $this->managerRegistry
            ->getManagerForClass(CalendarEvent::class)
            ->getRepository(CalendarEvent::class);
    }
}
