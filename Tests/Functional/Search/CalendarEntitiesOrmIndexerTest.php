<?php

declare(strict_types=1);

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Search;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Tests\Functional\Engine\AbstractEntitiesOrmIndexerTest;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Tests that CalendarEvent entities can be indexed without type casting errors with the ORM search engine.
 *
 * @group search
 * @dbIsolationPerTest
 */
class CalendarEntitiesOrmIndexerTest extends AbstractEntitiesOrmIndexerTest
{
    #[\Override]
    protected function getSearchableEntityClassesToTest(): array
    {
        return [CalendarEvent::class];
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOrganization::class, LoadUser::class]);

        $manager = $this->getDoctrine()->getManagerForClass(CalendarEvent::class);
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $calendar = $manager->getRepository(Calendar::class)->findDefaultCalendar(
            $user->getId(),
            $organization->getId()
        );

        $calendarEvent = (new CalendarEvent())
            ->setTitle('Test Calendar Event')
            ->setDescription('Test calendar event description')
            ->setStart(new \DateTime('2024-01-01 10:00:00', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('2024-01-01 11:00:00', new \DateTimeZone('UTC')))
            ->setCalendar($calendar);
        $this->persistTestEntity($calendarEvent);

        $manager->flush();
    }
}
