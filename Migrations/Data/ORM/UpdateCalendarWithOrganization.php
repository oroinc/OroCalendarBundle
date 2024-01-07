<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

/**
 * Sets a default organization to Calendar entity.
 */
class UpdateCalendarWithOrganization extends UpdateWithOrganization implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        /*
         * This fixture should be performed after `LoadOrganizationAndBusinessUnitData` fixture, but before any other
         * fixtures because user changes in another fixtures might provoke calendar creation
         */
        return -230;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->update($manager, Calendar::class);
    }
}
