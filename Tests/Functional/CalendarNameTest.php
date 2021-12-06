<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CalendarNameTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([]);
        $this->loadFixtures([LoadUserData::class]);
    }

    private function getEntityNameResolver(): EntityNameResolver
    {
        return self::getContainer()->get('oro_entity.entity_name_resolver');
    }

    /**
     * @param bool      $withName
     * @param string    $expectedEventName
     * @param bool      $withoutOwner
     *
     * @dataProvider dataProvider
     */
    public function testCalendarNameEqualsToExpected($withName, $expectedEventName, $withoutOwner)
    {
        /** @var Calendar $calendar */
        $calendar = $this->getReference('oro_calendar:calendar:system_user_1');
        if ($withName) {
            $calendar->setName($expectedEventName);
        } else {
            $calendar->setName(null);
        }

        if ($withoutOwner) {
            $calendar->setOwner(null);
        }

        $eventName = $this->getEntityNameResolver()->getName($calendar);
        $this->assertSame($expectedEventName, $eventName);
    }

    public function dataProvider(): array
    {
        return [
            'Event with name' => [
                'withName' => true,
                'expectedEventName' => 'calendarWithName',
                'withoutOwner' => false
            ],
            'Event without name' => [
                'withName' => false,
                'expectedEventName' => 'Elley Towards',
                'withoutOwner' => false
            ],
            'Event without owner' => [
                'withName' => false,
                'expectedEventName' => 'N/A',
                'withoutOwner'      => true
            ]
        ];
    }
}
