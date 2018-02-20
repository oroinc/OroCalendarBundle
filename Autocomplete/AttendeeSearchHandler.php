<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class AttendeeSearchHandler extends ContextSearchHandler
{
    /** @var AttendeeManager */
    protected $attendeeManager;

    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $groupped = $this->groupIdsByEntityName($items);

        $objects = [];
        foreach ($groupped as $entityName => $ids) {
            $objects = array_merge(
                $objects,
                $this->objectManager
                    ->getRepository($entityName)
                    ->findById($ids)
            );
        }

        $result = [];
        foreach ($objects as $object) {
            $attendee = $this->attendeeManager->createAttendee($object);

            $result[] = [
                'id'          => json_encode(
                    [
                        'entityClass' => ClassUtils::getClass($object),
                        'entityId'    => $object->getId(),
                    ]
                ),
                'text'        => $attendee->getDisplayName(),
                'displayName' => $attendee->getDisplayName(),
                'email'       => $attendee->getEmail(),
                'status'      => $attendee->getStatusCode(),
                'type'        => $attendee->getType() ? $attendee->getType()->getId() : null,
                'userId'      => $attendee->getUser() ? $attendee->getUser()->getId() : null
            ];
        }

        return $result;
    }

    /**
     * @param Item[] $items
     *
     * @return array
     */
    protected function groupIdsByEntityName(array $items)
    {
        $groupped = [];
        foreach ($items as $item) {
            $groupped[$item->getEntityName()][] = $item->getRecordId();
        }

        return $groupped;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchAliases()
    {
        return ['oro_user'];
    }

    /**
     * @param AttendeeManager $attendeeManager
     *
     * @return AttendeeSearchHandler
     */
    public function setAttendeeManager(AttendeeManager $attendeeManager)
    {
        $this->attendeeManager = $attendeeManager;

        return $this;
    }
}
