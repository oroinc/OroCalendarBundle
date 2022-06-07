<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\Entity\User;

class LoadSystemCalendarData extends AbstractFixture implements DependentFixtureInterface
{
    public const SYSTEM_CALENDAR_PUBLIC = 'system_calendar.public';
    public const SYSTEM_CALENDAR_ORGANIZATION = 'system_calendar.organization';

    /** @var array */
    private $calendars = [
        self::SYSTEM_CALENDAR_PUBLIC => [
            'name' => 'Public System Calendar',
            'public' => true
        ],
        self::SYSTEM_CALENDAR_ORGANIZATION => [
            'name' => 'Organization System Calendar',
            'public' => false
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);

        foreach ($this->calendars as $name => $data) {
            $systemCalendar = new SystemCalendar();
            $systemCalendar->setName($data['name']);
            $systemCalendar->setPublic($data['public']);
            $systemCalendar->setOrganization($user->getOrganization());

            $manager->persist($systemCalendar);

            $this->setReference($name, $systemCalendar);
        }

        $manager->flush();
    }
}
