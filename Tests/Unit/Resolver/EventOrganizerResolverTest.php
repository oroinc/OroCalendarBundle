<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Resolver\EventOrganizerResolver;
use Oro\Bundle\CalendarBundle\Tests\Unit\Entity\CalendarEventTest;
use Oro\Bundle\UserBundle\Entity\User;

class EventOrganizerResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EventOrganizerResolver */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var ManagerRegistry $registry */
        $registry = $this->mockManagerRegistry();
        $this->resolver = new EventOrganizerResolver($registry);
    }

    public function testResolverDoesNotWorkForSystemCalendarEvents()
    {
        $calendar = new SystemCalendar();
        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setSystemCalendar($calendar)
            ->setOrganizerEmail(CalendarEventTest::OWNER_EMAIL);

        $this->resolver->updateOrganizerInfo($calendarEvent);
        $this->assertNull($calendarEvent->isOrganizer());
        $this->assertNull($calendarEvent->getOrganizerDisplayName());
    }

    public function testResolverDoesNotWorkIfOrganizerEmailIsNull()
    {
        $calendarEvent = new CalendarEvent();

        $this->resolver->updateOrganizerInfo($calendarEvent);
        $this->assertNull($calendarEvent->isOrganizer());
        $this->assertNull($calendarEvent->getOrganizerDisplayName());
    }

    /**
     * @dataProvider organizerFetchedUserDisplayNameDataProvider
     * @param string|null $displayName
     * @param string      $expectedDisplayName
     */
    public function testOrganizerIsFetchedFromDBInCaseProvidedOrganizerEmailExistsInSystem(
        $displayName,
        $expectedDisplayName
    ) {
        $calendarEvent = CalendarEventTest::getCalendarEventWithOwner();
        $calendarEvent->setOrganizerEmail(CalendarEventTest::PROVIDED_EMAIL);
        if ($displayName) {
            $calendarEvent->setOrganizerDisplayName($displayName);
        }

        // user exists in DB
        $existingUser = $this->getExistingUser();

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($existingUser);

        $this->resolver->updateOrganizerInfo($calendarEvent);

        $this->assertFalse($calendarEvent->isOrganizer());
        $this->assertNotNull($calendarEvent->getOrganizerUser());
        $this->assertEquals(CalendarEventTest::PROVIDED_EMAIL, $calendarEvent->getOrganizerUser()->getEmail());
        $this->assertEquals(CalendarEventTest::PROVIDED_EMAIL, $calendarEvent->getOrganizerEmail());
        $this->assertEquals($expectedDisplayName, $calendarEvent->getOrganizerDisplayName());
    }

    /**
     * @dataProvider organizerEmailDisplayNameDataProvider
     * @param string|null $displayName
     * @param string      $expectedDisplayName
     */
    public function testOrganizerIsNullInCaseProvidedOrganizerEmailDoesNotExistsInSystem(
        $displayName,
        $expectedDisplayName
    ) {
        $calendarEvent = CalendarEventTest::getCalendarEventWithOwner();
        $calendarEvent->setOrganizerEmail(CalendarEventTest::PROVIDED_EMAIL);
        if ($displayName) {
            $calendarEvent->setOrganizerDisplayName($displayName);
        }

        // user does not exist in DB
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->resolver->updateOrganizerInfo($calendarEvent);

        $this->assertFalse($calendarEvent->isOrganizer());
        $this->assertNull($calendarEvent->getOrganizerUser());
        $this->assertEquals(CalendarEventTest::PROVIDED_EMAIL, $calendarEvent->getOrganizerEmail());
        $this->assertEquals($expectedDisplayName, $calendarEvent->getOrganizerDisplayName());
    }

    /**
     * @return array
     */
    public function organizerFetchedUserDisplayNameDataProvider()
    {
        return [
            [null, CalendarEventTest::PROVIDED_DISPLAY_NAME],
            ['custom name', 'custom name']
        ];
    }

    /**
     * @return array
     */
    public function organizerEmailDisplayNameDataProvider()
    {
        return [
            [null, CalendarEventTest::PROVIDED_EMAIL],
            ['custom name', 'custom name']
        ];
    }

    private function mockManagerRegistry()
    {
        $this->repository = $this->createMock(ObjectRepository::class);

        $manager = $this->createMock(ObjectManager::class);

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }

    private function getExistingUser(): User
    {
        $existingUser = new User();
        $existingUser
            ->setEmail(CalendarEventTest::PROVIDED_EMAIL)
            ->setFirstName(CalendarEventTest::PROVIDED_FIRST_NAME)
            ->setLastName(CalendarEventTest::PROVIDED_LAST_NAME);

        return $existingUser;
    }
}
