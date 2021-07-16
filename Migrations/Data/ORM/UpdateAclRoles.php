<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractUpdatePermissions;
use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\LoadAclRoles;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

/**
 * Updates permissions for CalendarEvent entity for ROLE_USER and ROLE_MANAGER roles.
 */
class UpdateAclRoles extends AbstractUpdatePermissions implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadAclRoles::class];
    }

    public function load(ObjectManager $manager)
    {
        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $this->setEntityPermissions(
            $aclManager,
            $this->getRole($manager, LoadRolesData::ROLE_USER),
            CalendarEvent::class,
            ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM']
        );
        $this->setEntityPermissions(
            $aclManager,
            $this->getRole($manager, LoadRolesData::ROLE_MANAGER),
            CalendarEvent::class,
            ['VIEW_SYSTEM', 'CREATE_SYSTEM', 'EDIT_SYSTEM', 'DELETE_SYSTEM']
        );
        $aclManager->flush();
    }
}
