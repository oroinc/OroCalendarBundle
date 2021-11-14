<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent as CalendarEventStub;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserCalendarEventNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    /** @var AttendeeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeeManager;

    /** @var ReminderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $reminderManager;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var UserCalendarEventNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->attendeeManager = $this->createMock(AttendeeManager::class);
        $this->reminderManager = $this->createMock(ReminderManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects($this->any())
            ->method('sanitize')
            ->willReturnCallback(function ($value) {
                return $value ? $value . 's' : $value;
            });

        $this->normalizer = new UserCalendarEventNormalizer(
            $this->calendarEventManager,
            $this->attendeeManager,
            $this->reminderManager,
            $this->authorizationChecker,
            $htmlTagHelper
        );
        $this->normalizer->setTokenAccessor($this->tokenAccessor);
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents(
        array $events,
        array $attendees,
        ?bool $editableInvitationStatus,
        array $expected
    ) {
        $calendarId = 123;

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($events);

        if (!empty($events)) {
            $this->authorizationChecker->expects($this->exactly(2))
                ->method('isGranted')
                ->willReturnMap([
                    ['oro_calendar_event_update', null, true],
                    ['oro_calendar_event_delete', null, true],
                ]);
        }

        if ($events) {
            $loggedUser = new User();

            $this->tokenAccessor->expects($this->once())
                ->method('getUser')
                ->willReturn($loggedUser);

            $this->calendarEventManager->expects($this->once())
                ->method('canChangeInvitationStatus')
                ->with($this->isType('array'), $loggedUser)
                ->willReturn($editableInvitationStatus);

            $this->attendeeManager->expects($this->once())
                ->method('getAttendeeListsByCalendarEventIds')
                ->willReturnCallback(function ($calendarEventIds) use ($attendees) {
                    return array_intersect_key($attendees, array_flip($calendarEventIds));
                });
        } else {
            $this->tokenAccessor->expects($this->never())
                ->method($this->anything());
            $this->calendarEventManager->expects($this->never())
                ->method($this->anything());
            $this->attendeeManager->expects($this->never())
                ->method($this->anything());
        }

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, CalendarEvent::class);

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCalendarEventsProvider(): array
    {
        $startDate = new \DateTime();
        $endDate = $startDate->add(new \DateInterval('PT1H'));

        return [
            'no events' => [
                'events' => [],
                'invitees' => [],
                'editableInvitationStatus' => null,
                'expected' => []
            ],
            'event without invitees' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                        'isOrganizer' => true,
                        'organizerEmail' => 'ja@oroinc.com',
                        'organizerDisplayName' => 'John Altovart',
                        'organizerUserId' => null
                    ],
                ],
                'attendees' => [1 => []],
                'editableInvitationStatus' => false,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'attendees' => [],
                        'editable' => true,
                        'editableInvitationStatus' => false,
                        'removable' => true,
                        'isOrganizer' => true,
                        'organizerEmail' => 'ja@oroinc.com',
                        'organizerDisplayName' => 'John Altovart',
                        'organizerUserId' => null
                    ],
                ]
            ],
            'event with invitees' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                        'isOrganizer' => true,
                        'organizerEmail' => 'ja@oroinc.com',
                        'organizerDisplayName' => 'John Altovart',
                        'organizerUserId' => 1
                    ],
                ],
                'attendees' => [
                    1 => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => null
                        ],
                    ],
                ],
                'editableInvitationStatus' => false,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'editable' => true,
                        'editableInvitationStatus' => false,
                        'removable' => true,
                        'attendees' => [
                            [
                                'displayName' => 'user',
                                'email' => 'user@example.com',
                                'userId' => null
                            ],
                        ],
                        'isOrganizer' => true,
                        'organizerEmail' => 'ja@oroinc.com',
                        'organizerDisplayName' => 'John Altovart',
                        'organizerUserId' => 1
                    ],
                ]
            ],
            'event with invitees and editable invitation status and organizer not specified (BC)' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => null,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                        'isOrganizer' => null,
                        'organizerEmail' => null,
                        'organizerDisplayName' => null,
                        'organizerUserId' => null
                    ],
                ],
                'attendees' => [
                    1 => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => 1
                        ],
                    ],
                ],
                'editableInvitationStatus' => true,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => null,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'editable' => true,
                        'editableInvitationStatus' => true,
                        'removable' => true,
                        'attendees' => [
                            [
                                'displayName' => 'user',
                                'email' => 'user@example.com',
                                'userId' => 1
                            ],
                        ],
                        'isOrganizer' => null,
                        'organizerEmail' => null,
                        'organizerDisplayName' => null,
                        'organizerUserId' => null
                    ],
                ]
            ],
            'event with invitees and editable invitation status and is organizer' => [
                'events' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => null,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate,
                        'end' => $endDate,
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'relatedAttendeeUserId' => 1,
                        'isOrganizer' => true,
                        'organizerEmail' => 'org@org.org',
                        'organizerDisplayName' => 'organizer',
                        'organizerUserId' => 1
                    ],
                ],
                'attendees' => [
                    1 => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => 1
                        ],
                    ],
                ],
                'editableInvitationStatus' => true,
                'expected' => [
                    [
                        'calendar' => 123,
                        'id' => 1,
                        'uid' => null,
                        'title' => 'test',
                        'description' => null,
                        'start' => $startDate->format('c'),
                        'end' => $endDate->format('c'),
                        'allDay' => false,
                        'backgroundColor' => null,
                        'createdAt' => null,
                        'updatedAt' => null,
                        'parentEventId' => null,
                        'invitationStatus' => Attendee::STATUS_NONE,
                        'editable' => true,
                        'editableInvitationStatus' => true,
                        'removable' => true,
                        'attendees' => [
                            [
                                'displayName' => 'user',
                                'email' => 'user@example.com',
                                'userId' => 1
                            ],
                        ],
                        'isOrganizer' => true,
                        'organizerEmail' => 'org@org.org',
                        'organizerDisplayName' => 'organizer',
                        'organizerUserId' => 1
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getCalendarEventProvider
     */
    public function testGetCalendarEvent(
        array $event,
        ?int $calendarId,
        bool $editableInvitationStatus,
        array $expected
    ) {
        $loggedUser = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->calendarEventManager->expects($this->once())
            ->method('canChangeInvitationStatus')
            ->with($this->isType('array'), $loggedUser)
            ->willReturn($editableInvitationStatus);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap([
                ['oro_calendar_event_update', null, true],
                ['oro_calendar_event_delete', null, true],
            ]);

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with([$expected], CalendarEvent::class);

        $this->attendeeManager->expects($this->never())
            ->method('getAttendeeListsByCalendarEventIds');

        $result = $this->normalizer->getCalendarEvent(
            $this->buildCalendarEvent($event),
            $calendarId
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCalendarEventProvider(): array
    {
        $startDate = new \DateTime();
        $endDate = $startDate->add(new \DateInterval('PT1H'));

        return [
            'calendar not specified' => [
                'event' => [
                    'calendar' => 123,
                    'id' => 1,
                    'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                    'title' => 'test',
                    'description' => 'test_description',
                    'start' => $startDate,
                    'end' => $endDate,
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'attendees' => [],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'isOrganizer' => null,
                    'organizerEmail' => null,
                    'organizerDisplayName' => null,
                    'organizerUserId' => null
                ],
                'calendarId' => null,
                'editableInvitationStatus' => false,
                'expected' => [
                    'calendar' => 123,
                    'id' => 1,
                    'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                    'title' => 'test',
                    'description' => 'test_descriptions',
                    'start' => $startDate->format('c'),
                    'end' => $endDate->format('c'),
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'editable' => true,
                    'editableInvitationStatus' => false,
                    'removable' => true,
                    'attendees' => [],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'isOrganizer' => null,
                    'organizerEmail' => null,
                    'organizerDisplayName' => null,
                    'organizerUserId' => null
                ]
            ],
            'own calendar' => [
                'event' => [
                    'calendar' => 123,
                    'id' => 1,
                    'uid' => null,
                    'title' => 'test',
                    'description' => 'test_description',
                    'start' => $startDate,
                    'end' => $endDate,
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'attendees' => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'status' => Attendee::STATUS_NONE,
                        ]
                    ],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'isOrganizer' => true,
                ],
                'calendarId' => 123,
                'editableInvitationStatus' => false,
                'expected' => [
                    'calendar' => 123,
                    'id' => 1,
                    'uid' => null,
                    'title' => 'test',
                    'description' => 'test_descriptions',
                    'start' => $startDate->format('c'),
                    'end' => $endDate->format('c'),
                    'allDay' => null,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'editable' => true,
                    'editableInvitationStatus' => false,
                    'removable' => true,
                    'attendees' => [
                        [
                            'displayName' => 'user',
                            'email' => 'user@example.com',
                            'userId' => null,
                            'createdAt' => null,
                            'updatedAt' => null,
                            'status' => Attendee::STATUS_NONE,
                            'type' => null,
                        ]
                    ],
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'isOrganizer' => true,
                    'organizerEmail' => null,
                    'organizerDisplayName' => null,
                    'organizerUserId' => null
                ]
            ],
            'another calendar' => [
                'event' => [
                    'calendar' => 123,
                    'id' => 1,
                    'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                    'title' => 'test',
                    'start' => $startDate,
                    'end' => $endDate,
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'isOrganizer' => false,
                    'organizerEmail' => 'ja@oroinc.com',
                    'organizerDisplayName' => 'John Altovart',
                    'organizerUserId' => 1
                ],
                'calendarId' => 456,
                'editableInvitationStatus' => false,
                'expected' => [
                    'calendar' => 123,
                    'id' => 1,
                    'uid' => 'b139fecc-41cf-478d-8f8e-b6122f491ace',
                    'title' => 'test',
                    'description' => null,
                    'start' => $startDate->format('c'),
                    'end' => $endDate->format('c'),
                    'allDay' => false,
                    'backgroundColor' => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                    'parentEventId' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'attendees' => [],
                    'editable' => false,
                    'editableInvitationStatus' => false,
                    'removable' => false,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'isOrganizer' => false,
                    'organizerEmail' => 'ja@oroinc.com',
                    'organizerDisplayName' => 'John Altovart',
                    'organizerUserId' => 1
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function buildCalendarEvent(array $data): CalendarEvent
    {
        $event = new CalendarEventStub();

        if (!empty($data['id'])) {
            ReflectionUtil::setId($event, $data['id']);
        }
        if (!empty($data['uid'])) {
            $event->setUid($data['uid']);
        }
        if (!empty($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (!empty($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (!empty($data['start'])) {
            $event->setStart($data['start']);
        }
        if (!empty($data['end'])) {
            $event->setEnd($data['end']);
        }
        if (isset($data['allDay'])) {
            $event->setAllDay($data['allDay']);
        }
        if (isset($data['isOrganizer'])) {
            $event->setIsOrganizer($data['isOrganizer']);
        }
        if (isset($data['organizerUserId'])) {
            $event->setOrganizerUser(new User($data['organizerUserId']));
        }
        if (isset($data['organizerEmail'])) {
            $event->setOrganizerEmail($data['organizerEmail']);
        }
        if (isset($data['organizerDisplayName'])) {
            $event->setOrganizerDisplayName($data['organizerDisplayName']);
        }
        if (!empty($data['calendar'])) {
            $calendar = new Calendar();
            ReflectionUtil::setId($calendar, $data['calendar']);
            $calendar->setOwner(new User(1));
            $event->setCalendar($calendar);
        }

        if (!empty($data['attendees'])) {
            foreach ($data['attendees'] as $attendeeData) {
                $attendee = new Attendee();
                $attendee->setEmail($attendeeData['email']);
                $attendee->setDisplayName($attendeeData['displayName']);

                if (array_key_exists('status', $attendeeData)) {
                    $status = new TestEnumValue($attendeeData['status'], $attendeeData['status']);
                    $attendee->setStatus($status);
                }

                $event->addAttendee($attendee);
            }
        }

        return $event;
    }
}
