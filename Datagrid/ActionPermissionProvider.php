<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ActionPermissionProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getInvitationPermissions(ResultRecordInterface $record)
    {
        /** @var User $user */
        $user             = $this->securityFacade->getLoggedUser();
        $invitationStatus = $record->getValue('invitationStatus');
        $parentId         = $record->getValue('parentId');
        $ownerId          = $record->getValue('ownerId');
        $attendeeUserId   = $record->getValue('attendeeUserId');
        $isEditable       = (!$invitationStatus || ($invitationStatus && !$parentId));

        return [
            'accept'      => $this->isAvailableResponseButton(
                $user,
                $ownerId,
                $attendeeUserId,
                $invitationStatus,
                CalendarEvent::STATUS_ACCEPTED
            ),
            'decline'     => $this->isAvailableResponseButton(
                $user,
                $ownerId,
                $attendeeUserId,
                $invitationStatus,
                CalendarEvent::STATUS_DECLINED
            ),
            'tentatively' => $this->isAvailableResponseButton(
                $user,
                $ownerId,
                $attendeeUserId,
                $invitationStatus,
                CalendarEvent::STATUS_TENTATIVE
            ),
            'view'        => true,
            'update'      => $isEditable
        ];
    }

    /**
     * @param User $user
     * @param int $ownerId
     * @param int $attendeeUserId
     * @param string $invitationStatus
     * @param string $buttonStatus
     * @return bool
     */
    protected function isAvailableResponseButton(
        $user,
        $ownerId,
        $attendeeUserId,
        $invitationStatus,
        $buttonStatus
    ) {
        return $invitationStatus
        && $invitationStatus != $buttonStatus
        && $user->getId() == $ownerId
        && $user->getId() == $attendeeUserId;
    }
}
