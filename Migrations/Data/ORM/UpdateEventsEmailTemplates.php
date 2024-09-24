<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractHashEmailMigration;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Updates email templates to new version matching old versions available for update by hashes
 */
class UpdateEventsEmailTemplates extends AbstractHashEmailMigration implements VersionedFixtureInterface
{
    #[\Override]
    protected function getEmailHashesToUpdate(): array
    {
        return [
            'calendar_reminder' => ['315c7086a0249bc981ff250a8c93f7f3']
        ];
    }

    #[\Override]
    public function getVersion(): string
    {
        return '1.0';
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/events');
    }
}
