<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadSystemCalendarData extends AbstractFixture implements DependentFixtureInterface
{
    public const SYSTEM_CALENDAR_PUBLIC = 'system_calendar.public';
    public const SYSTEM_CALENDAR_ORGANIZATION = 'system_calendar.organization';

    private array $calendars = [
        self::SYSTEM_CALENDAR_PUBLIC => [
            'name' => 'Public System Calendar',
            'public' => true
        ],
        self::SYSTEM_CALENDAR_ORGANIZATION => [
            'name' => 'Organization System Calendar',
            'public' => false
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->calendars as $name => $data) {
            $systemCalendar = new SystemCalendar();
            $systemCalendar->setName($data['name']);
            $systemCalendar->setPublic($data['public']);
            $systemCalendar->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $manager->persist($systemCalendar);
            $this->setReference($name, $systemCalendar);
        }
        $manager->flush();
    }
}
