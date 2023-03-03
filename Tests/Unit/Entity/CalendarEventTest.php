<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CalendarEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const OWNER_EMAIL = 'owner@example.com';
    private const OWNER_FIRST_NAME = 'Owner';
    private const OWNER_LAST_NAME = 'Name';
    private const OWNER_DISPLAY_NAME = 'Owner Name';

    private const PROVIDED_EMAIL = 'provided@example.com';
    private const PROVIDED_FIRST_NAME = 'Provided';
    private const PROVIDED_LAST_NAME = 'Name';
    private const PROVIDED_DISPLAY_NAME = 'Provided Name';

    public function testIdGetter()
    {
        $obj = new CalendarEvent();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $obj = new CalendarEvent();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertSame($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['calendar', new Calendar()],
            ['systemCalendar', new SystemCalendar()],
            ['title', 'testTitle'],
            ['description', 'testdDescription'],
            ['start', new \DateTime()],
            ['end', new \DateTime()],
            ['allDay', true],
            ['backgroundColor', '#FF0000'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['recurrence', new Recurrence()],
            ['originalStart', new \DateTime()],
            ['cancelled', true],
            ['parent', new CalendarEvent()],
            ['recurringEvent', new CalendarEvent()],
            ['relatedAttendee', new Attendee()],
            ['reminders', new ArrayCollection()],
        ];
    }

    public function testFindRelatedAttendeeExist()
    {
        $user = new User();
        $calendar = new Calendar();
        $calendar->setOwner($user);

        $attendee = new Attendee();
        $attendee->setUser($user);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);
        $calendarEvent->addAttendee($attendee);

        $this->assertSame($attendee, $calendarEvent->findRelatedAttendee());
    }

    public function testFindRelatedAttendeeDoesNotExistWhenCalendarHasNoOwner()
    {
        $user = new User();
        $calendar = new Calendar();

        $attendee = new Attendee();
        $attendee->setUser($user);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);
        $calendarEvent->addAttendee($attendee);

        $this->assertEmpty($calendarEvent->findRelatedAttendee());
    }

    public function testFindRelatedAttendeeDoesNotExistWhenEventHasNoCalendar()
    {
        $user = new User();

        $attendee = new Attendee();
        $attendee->setUser($user);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->addAttendee($attendee);

        $this->assertEmpty($calendarEvent->findRelatedAttendee());
    }

    public function testFindRelatedAttendeeDoesNotExistWhenCalendarOwnerDoesNotMatch()
    {
        $userOwner = new User();
        $userOwner->setId(100);
        $calendar = new Calendar();
        $calendar->setOwner($userOwner);

        $userAttendee = new User();
        $userAttendee->setId(200);
        $attendee = new Attendee();
        $attendee->setUser($userAttendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);
        $calendarEvent->addAttendee($attendee);

        $this->assertEmpty($calendarEvent->findRelatedAttendee());
    }

    public function testInvitationStatus()
    {
        $user = new User();
        $calendar = new Calendar();
        $calendar->setOwner($user);

        $attendee = new Attendee();
        $attendee->setUser($user);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);
        $calendarEvent->setRelatedAttendee($attendee);

        $attendee->setStatus(
            new TestEnumValue(Attendee::STATUS_ACCEPTED, Attendee::STATUS_ACCEPTED)
        );
        $this->assertEquals(Attendee::STATUS_ACCEPTED, $calendarEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_ACCEPTED, $calendarEvent->getRelatedAttendee()->getStatus());

        $attendee->setStatus(
            new TestEnumValue(Attendee::STATUS_TENTATIVE, Attendee::STATUS_TENTATIVE)
        );
        $this->assertEquals(Attendee::STATUS_TENTATIVE, $calendarEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_TENTATIVE, $calendarEvent->getRelatedAttendee()->getStatus());
    }

    public function testInvitationStatusNoneWhenAttendeesDoNotExist()
    {
        $user = new User();
        $calendar = new Calendar();
        $calendar->setOwner($user);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);

        $this->assertEquals(Attendee::STATUS_NONE, $calendarEvent->getInvitationStatus());
    }

    public function testChildren()
    {
        $calendarEventOne = new CalendarEvent();
        $calendarEventOne->setTitle('First calendar event');
        $calendarEventTwo = new CalendarEvent();
        $calendarEventOne->setTitle('Second calendar event');
        $calendarEventThree = new CalendarEvent();
        $calendarEventOne->setTitle('Third calendar event');
        $children = [$calendarEventOne, $calendarEventTwo];

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle('Parent calendar event');

        // reset children calendar events
        $this->assertSame($calendarEvent, $calendarEvent->resetChildEvents($children));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals($children, $actual->toArray());
        /** @var CalendarEvent $child */
        foreach ($children as $child) {
            $this->assertEquals($calendarEvent->getTitle(), $child->getParent()->getTitle());
        }

        // add children calendar events
        $this->assertSame($calendarEvent, $calendarEvent->addChildEvent($calendarEventTwo));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals($children, $actual->toArray());

        $this->assertSame($calendarEvent, $calendarEvent->addChildEvent($calendarEventThree));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals([$calendarEventOne, $calendarEventTwo, $calendarEventThree], $actual->toArray());
        /** @var CalendarEvent $child */
        foreach ($children as $child) {
            $this->assertEquals($calendarEvent->getTitle(), $child->getParent()->getTitle());
        }

        // remove child calender event
        $this->assertSame($calendarEvent, $calendarEvent->removeChildEvent($calendarEventOne));
        $actual = $calendarEvent->getChildEvents();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals([1 => $calendarEventTwo, 2 => $calendarEventThree], $actual->toArray());
    }

    public function testGetChildEventByCalendar()
    {
        $firstCalendar = new Calendar(1);
        $secondCalendar = new Calendar(2);

        $firstEvent = new CalendarEvent(1);
        $firstEvent->setTitle('1')
            ->setCalendar($firstCalendar);
        $secondEvent = new CalendarEvent(2);
        $secondEvent->setTitle('2')
            ->setCalendar($secondCalendar);

        $masterEvent = new CalendarEvent();
        $masterEvent->addChildEvent($firstEvent)
            ->addChildEvent($secondEvent);

        $this->assertEquals($firstEvent, $masterEvent->getChildEventByCalendar($firstCalendar));
        $this->assertEquals($secondEvent, $masterEvent->getChildEventByCalendar($secondCalendar));
        $this->assertNull($masterEvent->getChildEventByCalendar(new Calendar));
    }

    public function testGetReminderData()
    {
        $obj = new CalendarEvent();
        ReflectionUtil::setId($obj, 1);
        $obj->setTitle('testTitle');
        $calendar = new Calendar();
        $calendar->setOwner(new User());
        $obj->setCalendar($calendar);
        /** @var ReminderData $reminderData */
        $reminderData = $obj->getReminderData();

        $this->assertEquals($reminderData->getSubject(), $obj->getTitle());
        $this->assertEquals($reminderData->getExpireAt(), $obj->getStart());
        $this->assertSame($reminderData->getRecipient(), $calendar->getOwner());
    }

    public function testGetReminderDataWithLogicException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Only user's calendar events can have reminders. Event Id: 1.");

        $obj = new CalendarEvent();
        ReflectionUtil::setId($obj, 1);
        $obj->getReminderData();
    }

    public function testToString()
    {
        $obj = new CalendarEvent();
        $obj->setTitle('testTitle');
        $this->assertEquals($obj->getTitle(), (string)$obj);
    }

    public function testGetCalendarUidNoCalendar()
    {
        $obj = new CalendarEvent();
        $this->assertNull($obj->getCalendarUid());
    }

    public function testGetCalendarUidUserCalendar()
    {
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 123);

        $obj = new CalendarEvent();
        $obj->setCalendar($calendar);
        $this->assertEquals('user_123', $obj->getCalendarUid());
    }

    public function testGetCalendarUidSystemCalendar()
    {
        $calendar = new SystemCalendar();
        ReflectionUtil::setId($calendar, 123);

        $obj = new CalendarEvent();
        $obj->setSystemCalendar($calendar);
        $this->assertEquals('system_123', $obj->getCalendarUid());
    }

    public function testGetCalendarUidPublicCalendar()
    {
        $calendar = new SystemCalendar();
        ReflectionUtil::setId($calendar, 123);
        $calendar->setPublic(true);

        $obj = new CalendarEvent();
        $obj->setSystemCalendar($calendar);
        $this->assertEquals('public_123', $obj->getCalendarUid());
    }

    public function testSetCalendar()
    {
        $calendar = new Calendar();
        $systemCalendar = new SystemCalendar();

        $obj = new CalendarEvent();

        $this->assertNull($obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());

        $obj->setCalendar($calendar);
        $this->assertSame($calendar, $obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());

        $obj->setSystemCalendar($systemCalendar);
        $this->assertNull($obj->getCalendar());
        $this->assertSame($systemCalendar, $obj->getSystemCalendar());

        $obj->setCalendar($calendar);
        $this->assertSame($calendar, $obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());

        $obj->setCalendar(null);
        $this->assertNull($obj->getCalendar());

        $obj->setSystemCalendar($systemCalendar);
        $this->assertNull($obj->getCalendar());
        $this->assertSame($systemCalendar, $obj->getSystemCalendar());

        $obj->setSystemCalendar(null);
        $this->assertNull($obj->getCalendar());
        $this->assertNull($obj->getSystemCalendar());
    }

    public function testIsUpdatedFlags()
    {
        $date = new \DateTime('2012-12-12 12:12:12');
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUpdatedAt($date);

        $this->assertTrue($calendarEvent->isUpdatedAtSet());
    }

    public function testIsNotUpdatedFlags()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUpdatedAt(null);

        $this->assertFalse($calendarEvent->isUpdatedAtSet());
    }

    public function testAttendees()
    {
        $attendee = $this->createMock(\Oro\Bundle\CalendarBundle\Entity\Attendee::class);
        $attendees = new ArrayCollection([$attendee]);

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setAttendees($attendees);

        $this->assertCount(1, $calendarEvent->getAttendees());

        $calendarEvent->addAttendee(clone $attendee);

        $this->assertCount(2, $calendarEvent->getAttendees());

        foreach ($calendarEvent->getAttendees() as $item) {
            $this->assertInstanceOf(\Oro\Bundle\CalendarBundle\Entity\Attendee::class, $item);
        }

        $calendarEvent->removeAttendee($attendee);

        $this->assertCount(1, $calendarEvent->getAttendees());
    }

    public function testAddAttendeeFailsWithChildEvent()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Update of child Calendar Event (id=2) is restricted. Use parent Calendar Event instead.'
        );

        $parentEvent = new CalendarEvent(1);
        $parentEvent->setTitle('First calendar event');
        $childEvent = new CalendarEvent(2);
        $childEvent->setTitle('Second calendar event');
        $childEvent->setParent($parentEvent);
        $childEvent->addAttendee(new Attendee(1));
    }

    public function testRemoveAttendeeFailsWithChildEvent()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Update of child Calendar Event (id=2) is restricted. Use parent Calendar Event instead.'
        );

        $parentEvent = new CalendarEvent(1);
        $parentEvent->setTitle('First calendar event');
        $childEvent = new CalendarEvent(2);
        $childEvent->setTitle('Second calendar event');
        $childEvent->setParent($parentEvent);
        $childEvent->removeAttendee(new Attendee(1));
    }

    public function testGetAttendeesWorksWithChildEvent()
    {
        $parentEvent = new CalendarEvent();
        $parentEvent->setTitle('First calendar event');
        $childEvent = new CalendarEvent();
        $childEvent->setTitle('Second calendar event');
        $childEvent->setParent($parentEvent);

        $parentEvent->addAttendee(new Attendee(1));
        $parentEvent->addAttendee(new Attendee(2));
        $parentEvent->addAttendee(new Attendee(3));
        $parentEvent->addAttendee(new Attendee(4));

        $this->assertCount(4, $parentEvent->getAttendees());
        $this->assertCount(4, $childEvent->getAttendees());
    }

    public function testSetAttendeesFailsWithChildEvent()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Update of child Calendar Event (id=2) is restricted. Use parent Calendar Event instead.'
        );

        $parentEvent = new CalendarEvent(1);
        $parentEvent->setTitle('First calendar event');
        $childEvent = new CalendarEvent(2);
        $childEvent->setTitle('Second calendar event');
        $childEvent->setParent($parentEvent);

        $childEvent->setAttendees(new ArrayCollection([new Attendee(1)]));
    }

    /**
     * @dataProvider childAttendeesProvider
     */
    public function testGetChildAttendees(CalendarEvent $event, array $expectedAttendees)
    {
        $this->assertEquals($expectedAttendees, array_values($event->getChildAttendees()->toArray()));
    }

    public function childAttendeesProvider(): array
    {
        $userCalendarOwnerEmail = 'owner@example.com';
        $calendarOwner = new User();
        $calendarOwner->setId(100);
        $calendarOwner->setEmail($userCalendarOwnerEmail);
        $calendar = new Calendar();
        $calendar->setOwner($calendarOwner);

        $user1 = new User();
        $user1->setId(1);
        $attendee1 = (new Attendee())->setEmail('first@example.com')->setUser($user1);

        $user2 = new User();
        $user2->setId(2);
        $attendee2 = (new Attendee())->setEmail('second@example.com')->setUser($user2);

        $user3 = new User();
        $user3->setId(3);
        $attendee3 = (new Attendee())->setEmail('third@example.com')->setUser($user3);

        $attendeeWithSameCalendarOwnerUser = (new Attendee())->setEmail($calendarOwner)->setUser($calendarOwner);

        return [
            'event without related attendee' => [
                (new CalendarEvent())
                    ->setCalendar($calendar)
                    ->setAttendees(
                        new ArrayCollection(
                            [
                                $attendee1,
                                $attendee2,
                                $attendee3,
                            ]
                        )
                    ),
                [
                    $attendee1,
                    $attendee2,
                    $attendee3,
                ],
            ],
            'event with related attendee' => [
                (new CalendarEvent())
                    ->setCalendar($calendar)
                    ->setAttendees(
                        new ArrayCollection(
                            [
                                $attendeeWithSameCalendarOwnerUser,
                                $attendee2,
                                $attendee3,
                            ]
                        )
                    )
                    ->setRelatedAttendee($attendeeWithSameCalendarOwnerUser),
                [
                    $attendee2,
                    $attendee3,
                ],
            ]
        ];
    }

    public function testGetChildAttendeesFailsWithChildEvent()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Update of child Calendar Event (id=2) is restricted. Use parent Calendar Event instead.'
        );

        $parentEvent = new CalendarEvent(1);
        $parentEvent->setTitle('First calendar event');
        $childEvent = new CalendarEvent(2);
        $childEvent->setTitle('Second calendar event');
        $childEvent->setParent($parentEvent);

        $childEvent->getChildAttendees();
    }

    public function testExceptions()
    {
        $exceptionOne = new CalendarEvent();
        $exceptionOne->setTitle('First calendar event exception');
        $exceptionTwo = new CalendarEvent();
        $exceptionOne->setTitle('Second calendar event exception');
        $exceptionThree = new CalendarEvent();
        $exceptionOne->setTitle('Third calendar event exception');
        $exceptions = [$exceptionOne, $exceptionTwo];

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle('Exception parent calendar event');

        // reset exceptions
        $this->assertSame($calendarEvent, $calendarEvent->resetRecurringEventExceptions($exceptions));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals($exceptions, $actual->toArray());
        /** @var CalendarEvent $exception */
        foreach ($exceptions as $exception) {
            $this->assertEquals($calendarEvent->getTitle(), $exception->getRecurringEvent()->getTitle());
        }

        // add exception calendar events
        $this->assertSame($calendarEvent, $calendarEvent->addRecurringEventException($exceptionTwo));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals($exceptions, $actual->toArray());

        $this->assertSame($calendarEvent, $calendarEvent->addRecurringEventException($exceptionThree));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals([$exceptionOne, $exceptionTwo, $exceptionThree], $actual->toArray());
        /** @var CalendarEvent $exception */
        foreach ($exceptions as $exception) {
            $this->assertEquals($calendarEvent->getTitle(), $exception->getRecurringEvent()->getTitle());
        }

        // remove exception from calender event
        $this->assertSame($calendarEvent, $calendarEvent->removeRecurringEventException($exceptionOne));
        $actual = $calendarEvent->getRecurringEventExceptions();
        $this->assertInstanceOf(ArrayCollection::class, $actual);
        $this->assertEquals([1 => $exceptionTwo, 2 => $exceptionThree], $actual->toArray());
    }

    public function testGetAttendeeByEmailReturnsTrueWhenAttendeeIsMatched()
    {
        $email = 'test@example.com';
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->once())
            ->method('isEmailEqual')
            ->with($email)
            ->willReturn(false);

        $attendee2->expects($this->once())
            ->method('isEmailEqual')
            ->with($email)
            ->willReturn(true);

        $this->assertSame($attendee2, $event->getAttendeeByEmail($email));
    }

    public function testGetAttendeeByEmailReturnsFalseWhenNoAttendeesExist()
    {
        $email = 'test@example.com';
        $event = new CalendarEvent();

        $this->assertNull($event->getAttendeeByEmail($email));
    }

    public function testGetAttendeeByEmailReturnsFalseWhenNoAttendeeIsMatched()
    {
        $email = 'test@example.com';
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->once())
            ->method('isEmailEqual')
            ->with($email)
            ->willReturn(false);

        $attendee2->expects($this->once())
            ->method('isEmailEqual')
            ->with($email)
            ->willReturn(false);

        $this->assertNull($event->getAttendeeByEmail($email));
    }

    public function testGetAttendeeByUserReturnsTrueWhenAttendeeIsMatched()
    {
        $user = new User();
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(false);

        $attendee2->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(true);

        $this->assertSame($attendee2, $event->getAttendeeByUser($user));
    }

    public function testGetAttendeeByUserReturnsFalseWhenNoAttendeesExist()
    {
        $user = new User();
        $event = new CalendarEvent();

        $this->assertNull($event->getAttendeeByUser($user));
    }

    public function testGetAttendeeByUserReturnsFalseWhenNoAttendeeIsMatched()
    {
        $user = new User();
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(false);

        $attendee2->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(false);

        $this->assertNull($event->getAttendeeByUser($user));
    }

    public function testGetAttendeeByCalendarReturnsTrueWhenAttendeeIsMatched()
    {
        $user = new User();
        $calendar = new Calendar();
        $calendar->setOwner($user);
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(false);

        $attendee2->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(true);

        $this->assertSame($attendee2, $event->getAttendeeByCalendar($calendar));
    }

    public function testGetAttendeeByCalendarReturnsFalseWhenNoAttendeesExist()
    {
        $user = new User();
        $calendar = new Calendar();
        $calendar->setOwner($user);
        $event = new CalendarEvent();

        $this->assertNull($event->getAttendeeByCalendar($calendar));
    }

    public function testGetAttendeeByCalendarReturnsFalseWhenNoAttendeeIsMatched()
    {
        $user = new User();
        $calendar = new Calendar();
        $calendar->setOwner($user);
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(false);

        $attendee2->expects($this->once())
            ->method('isUserEqual')
            ->with($user)
            ->willReturn(false);

        $this->assertNull($event->getAttendeeByCalendar($calendar));
    }

    public function testGetAttendeeByCalendarReturnsFalseWhenCalendarHasNoOwner()
    {
        $calendar = new Calendar();
        $event = new CalendarEvent();

        $attendee1 = $this->createMock(Attendee::class);
        $attendee2 = $this->createMock(Attendee::class);

        $event->addAttendee($attendee1);
        $event->addAttendee($attendee2);

        $attendee1->expects($this->never())
            ->method($this->anything());

        $attendee2->expects($this->never())
            ->method($this->anything());

        $this->assertNull($event->getAttendeeByCalendar($calendar));
    }

    /**
     * @dataProvider organizerOwnerDisplayNameDataProvider
     */
    public function testOrganizerIsFetchedFromOwnerInCaseOrganizerEmailIsNotProvided(
        ?string $displayName,
        string  $expectedDisplayName
    ) {
        $calendarEvent = $this->getCalendarEventWithOwner();
        if ($displayName) {
            $calendarEvent->setOrganizerDisplayName($displayName);
        }

        $calendarEvent->calculateIsOrganizer();

        $this->assertTrue($calendarEvent->isOrganizer());
        $this->assertNotNull($calendarEvent->getOrganizerUser());
        $expectedEmail = $calendarEvent->getCalendar()->getOwner()->getEmail();
        $this->assertEquals($expectedEmail, $calendarEvent->getOrganizerUser()->getEmail());
        $this->assertEquals($expectedEmail, $calendarEvent->getOrganizerEmail());
        $this->assertEquals($expectedDisplayName, $calendarEvent->getOrganizerDisplayName());
    }

    /**
     * @dataProvider organizerOwnerDisplayNameDataProvider
     */
    public function testOrganizerIsSameAsOwnerInCaseProvidedEmailIsTheSameAsOwnerEmail(
        ?string $displayName,
        string $expectedDisplayName
    ) {
        $calendarEvent = $this->getCalendarEventWithOwner();
        $calendarEvent->setOrganizerEmail(self::OWNER_EMAIL);
        if ($displayName) {
            $calendarEvent->setOrganizerDisplayName($displayName);
        }

        $calendarEvent->calculateIsOrganizer();

        $this->assertTrue($calendarEvent->isOrganizer());
        $this->assertNotNull($calendarEvent->getOrganizerUser());
        $owner = $calendarEvent->getCalendar()->getOwner();
        $this->assertSame($owner, $calendarEvent->getOrganizerUser());
        $this->assertEquals($owner->getEmail(), $calendarEvent->getOrganizerEmail());
        $this->assertEquals($expectedDisplayName, $calendarEvent->getOrganizerDisplayName());
    }

    public function testCalculateIsOrganizerDoesNotWorkForSystemCalendarEvents()
    {
        $calendar = new SystemCalendar();
        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setSystemCalendar($calendar)
            ->setOrganizerEmail(self::OWNER_EMAIL);

        $calendarEvent->calculateIsOrganizer();

        $this->assertNull($calendarEvent->isOrganizer());
        $this->assertNull($calendarEvent->getOrganizerDisplayName());
    }

    public function testCalculateIsOrganizerDoesNotWorkIfCalendarIsNull()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setOrganizerEmail(self::OWNER_EMAIL);

        $calendarEvent->calculateIsOrganizer();

        $this->assertNull($calendarEvent->isOrganizer());
        $this->assertNull($calendarEvent->getOrganizerDisplayName());
    }

    /**
     * @dataProvider additionalFieldsProvider
     */
    public function testAdditionalFields(array $eventFields, array $expectedFields)
    {
        /** @var CalendarEvent $calendarEvent */
        $calendarEvent = $this->getEntity(CalendarEvent::class, $eventFields);

        $this->assertSame($expectedFields, $calendarEvent->getAdditionalFields());
    }

    public function additionalFieldsProvider(): array
    {
        return [
            [['id' => 1, 'uid' => 'UUID-1'], ['uid' => 'UUID-1', 'calendar_id' => null]],
            [
                ['id' => 2, 'uid' => 'UUID-1', 'calendar' => new Calendar(1)],
                ['uid' => 'UUID-1', 'calendar_id' => 1]
            ],
            [['id' => 3, 'calendar' => new Calendar(2)], ['uid' => null, 'calendar_id' => 2]],
        ];
    }

    public function organizerOwnerDisplayNameDataProvider(): array
    {
        return [
            [null, self::OWNER_DISPLAY_NAME],
            ['custom name', 'custom name']
        ];
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
