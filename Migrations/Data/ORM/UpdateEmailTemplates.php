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
            'calendar_invitation_accepted' => ['5813143a791f57fb3158d7b4960122ec'],
            'calendar_invitation_declined' => ['c91fcd2ebe0d4e4b9e4fa2df963cc3ba'],
            'calendar_invitation_delete_child_event' => ['1c10bdd969f45db6d0a94622a24b59af'],
            'calendar_invitation_delete_parent_event' => ['b68f76873d6b3943fad494a8bb67509f'],
            'calendar_invitation_invite' => ['7c13cd69c7c8c3339228d3543b75772f'],
            'calendar_invitation_tentative' => ['e6e60b036ac99e839d4f8174132b7361'],
            'calendar_invitation_uninvite' => ['1c0974b516ec547ed5e8fd25d7075572'],
            'calendar_invitation_update' => ['20f589fcdc7f97fe8b0b4977ba1585a7'],
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
