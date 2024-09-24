<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOrganization::class]);
    }

    private function getRepository(): CalendarRepository
    {
        return $this->getDoctrine()->getRepository(Calendar::class);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    public function testFindDefaultCalendars(): void
    {
        $repository = $this->getRepository();
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $userIds = [];
        $expectedCalendars = [];
        $users = $this->getDoctrine()->getRepository(User::class)
            ->findBy(['organization' => $organization]);
        foreach ($users as $user) {
            $userId = $user->getId();
            $calendar = $repository->findDefaultCalendar($userId, $organization->getId());
            if ($calendar) {
                $userIds[] = $userId;
                $expectedCalendars[] = $calendar;
            }
        }

        $this->assertEquals(
            $expectedCalendars,
            $repository->findDefaultCalendars($userIds, $organization->getId())
        );
    }
}
