<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;

/**
 * Loads email templates.
 */
class LoadEmailTemplates extends AbstractEmailFixture
{
    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/events');
    }
}
