<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DashboardBundle\Migrations\Data\ORM\AbstractDashboardFixture;
use Oro\Bundle\DashboardBundle\Migrations\Data\ORM\LoadDashboardData as LoadMainDashboardData;

/**
 * Adds "my_calendar" widget to "main" dashboard.
 */
class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadMainDashboardData::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $mainDashboard = $this->findAdminDashboardModel($manager, 'main');
        if ($mainDashboard) {
            $mainDashboard->addWidget($this->createWidgetModel('my_calendar', [1, 20]));
            $manager->flush();
        }
    }
}
