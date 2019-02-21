<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Updates email templates to new version matching old versions available for update by hashes
 */
class UpdateEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'calendar_invitation_accepted' => ['ded063280f6463f1f30093c00e58b380'],
            'calendar_invitation_declined' => ['2699723490ba63ffdf8cd76292bd820c'],
            'calendar_invitation_delete_child_event' => ['740d3535b2e4041de1d4f1a274e4e2a1'],
            'calendar_invitation_delete_parent_event' => ['b68f76873d6b3943fad494a8bb67509f'],
            'calendar_invitation_invite' => ['0abf7c7a3fc7e8157b9e1bd689dae9ec'],
            'calendar_invitation_tentative' => ['4e6dc61a75709a675ad6c1bb66f2e215'],
            'calendar_invitation_uninvite' => ['473e922ff0996f0b70b76c6f0f134810'],
            'calendar_invitation_update' => ['f07c69062bd6f51b698946a94fc98bdc'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/v1_0');
    }
}
