<?php

namespace Oro\Bundle\CalendarBundle\Ownership;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentChecker;

/**
 * The owner assignment checker for Calendar entity.
 */
class CalendarOwnerAssignmentChecker extends OwnerAssignmentChecker
{
    /**
     * {@inheritdoc}
     */
    protected function getHasAssignmentsQueryBuilder(
        $ownerId,
        string $entityClassName,
        string $ownerFieldName,
        EntityManagerInterface $em
    ): QueryBuilder {
        $qb = parent::getHasAssignmentsQueryBuilder($ownerId, $entityClassName, $ownerFieldName, $em);

        $qbParam = $em->createQueryBuilder()
            ->from(CalendarEvent::class, 'calendarEvents')
            ->select('calendarEvents')
            ->innerJoin('calendarEvents.calendar', 'calendar')
            ->where('calendar.id = entity.id');

        // if a default calendar (its name is NULL) has no events assume that it can be deleted
        // without any confirmation and as result we can remove such calendar from assignment list
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNotNull('entity.name'),
                $qb->expr()->andX(
                    $qb->expr()->isNull('entity.name'),
                    $qb->expr()->exists($qbParam)
                )
            )
        );

        return $qb;
    }
}
