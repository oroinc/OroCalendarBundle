<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

/**
 * Loads email templates for calendar event invitations.
 */
class LoadInvitationEmailTemplates extends AbstractEmailFixture implements VersionedFixtureInterface
{
    #[\Override]
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (empty($template['name'])) {
            return null;
        }

        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['name'],
            'entityName' => CalendarEvent::class
        ]);
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/invitation');
    }

    #[\Override]
    public function getVersion(): string
    {
        return '1.1';
    }
}
