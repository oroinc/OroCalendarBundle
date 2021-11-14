<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Resolver\EventOrganizerResolver;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\UserBundle\Entity\User;

class EventOrganizerResolverTest extends \PHPUnit\Framework\TestCase
{
    private const OWNER_EMAIL = 'owner@example.com';
    private const OWNER_FIRST_NAME = 'Owner';
    private const OWNER_LAST_NAME = 'Name';

    private const PROVIDED_EMAIL = 'provided@example.com';
    private const PROVIDED_FIRST_NAME = 'Provided';
    private const PROVIDED_LAST_NAME = 'Name';
    private const PROVIDED_DISPLAY_NAME = 'Provided Name';

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var EventOrganizerResolver */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ObjectRepository::class);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver = new EventOrganizerResolver($doctrine);
    }

    public function testResolverDoesNotWorkForSystemCalendarEvents()
    {
        $calendar = new SystemCalendar();
        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setSystemCalendar($calendar)
            ->setOrganizerEmail(self::OWNER_EMAIL);

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
     */
    public function testOrganizerIsFetchedFromDBInCaseProvidedOrganizerEmailExistsInSystem(
        ?string $displayName,
        string $expectedDisplayName
    ) {
        $calendarEvent = $this->getCalendarEventWithOwner();
        $calendarEvent->setOrganizerEmail(self::PROVIDED_EMAIL);
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
        $this->assertEquals(self::PROVIDED_EMAIL, $calendarEvent->getOrganizerUser()->getEmail());
        $this->assertEquals(self::PROVIDED_EMAIL, $calendarEvent->getOrganizerEmail());
        $this->assertEquals($expectedDisplayName, $calendarEvent->getOrganizerDisplayName());
    }

    /**
     * @dataProvider organizerEmailDisplayNameDataProvider
     */
    public function testOrganizerIsNullInCaseProvidedOrganizerEmailDoesNotExistsInSystem(
        ?string $displayName,
        string $expectedDisplayName
    ) {
        $calendarEvent = $this->getCalendarEventWithOwner();
        $calendarEvent->setOrganizerEmail(self::PROVIDED_EMAIL);
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
        $this->assertEquals(self::PROVIDED_EMAIL, $calendarEvent->getOrganizerEmail());
        $this->assertEquals($expectedDisplayName, $calendarEvent->getOrganizerDisplayName());
    }

    public function organizerFetchedUserDisplayNameDataProvider(): array
    {
        return [
            [null, self::PROVIDED_DISPLAY_NAME],
            ['custom name', 'custom name']
        ];
    }

    public function organizerEmailDisplayNameDataProvider(): array
    {
        return [
            [null, self::PROVIDED_EMAIL],
            ['custom name', 'custom name']
        ];
    }

    private function getExistingUser(): User
    {
        $existingUser = new User();
        $existingUser
            ->setEmail(self::PROVIDED_EMAIL)
            ->setFirstName(self::PROVIDED_FIRST_NAME)
            ->setLastName(self::PROVIDED_LAST_NAME);

        return $existingUser;
    }

    private function getCalendarEventWithOwner(): CalendarEvent
    {
        $calendarEvent = new CalendarEvent();
        $calendar = new Calendar();
        $calendarOwner = new User();
        $calendarOwner
            ->setEmail(self::OWNER_EMAIL)
            ->setFirstName(self::OWNER_FIRST_NAME)
            ->setLastName(self::OWNER_LAST_NAME);

        $calendar->setOwner($calendarOwner);
        $calendarEvent->setCalendar($calendar);

        return $calendarEvent;
    }
}
