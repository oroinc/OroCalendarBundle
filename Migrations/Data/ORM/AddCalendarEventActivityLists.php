<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Migrations\Data\ORM\AddActivityListsData;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * Adds activity lists for CalendarEvent entity.
 */
class AddCalendarEventActivityLists extends AddActivityListsData implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [UpdateCalendarWithOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->addActivityListsForActivityClass(
            $manager,
            CalendarEvent::class,
            'calendar.owner',
            'calendar.organization'
        );
    }
}
