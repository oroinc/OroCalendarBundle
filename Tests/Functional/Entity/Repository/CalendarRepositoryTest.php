<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getRepository(): CalendarRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Calendar::class);
    }

    public function testFindDefaultCalendars()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $userRepository = $doctrine->getRepository(User::class);
        $organizationRepository = $doctrine->getRepository(Organization::class);

        $firstOrganization = $organizationRepository->getFirst();
        $firstOrganizationId = $firstOrganization->getId();

        $userIds = [];
        $expectedCalendars = [];

        $users = $userRepository->findBy(['organization' => $firstOrganization]);
        foreach ($users as $user) {
            $userId = $user->getId();
            $calendar = $this->getRepository()->findDefaultCalendar($userId, $firstOrganizationId);
            if ($calendar) {
                $userIds[] = $userId;
                $expectedCalendars[] = $calendar;
            }
        }

        $this->assertEquals(
            $expectedCalendars,
            $this->getRepository()->findDefaultCalendars($userIds, $firstOrganizationId)
        );
    }
}
