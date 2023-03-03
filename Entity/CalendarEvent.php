<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Model\ExtendActivity;
use Oro\Bundle\CalendarBundle\Exception\NotUserCalendarEvent;
use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Calendar Event ORM Entity.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository")
 * @ORM\Table(
 *      name="oro_calendar_event",
 *      indexes={
 *          @ORM\Index(name="oro_calendar_event_idx", columns={"calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_sys_calendar_event_idx", columns={"system_calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_calendar_event_up_idx", columns={"updated_at"}),
 *          @ORM\Index(name="oro_calendar_event_osa_idx", columns={"original_start_at"}),
 *          @ORM\Index(name="oro_calendar_event_uid_idx", columns={"calendar_id", "uid"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_calendar_view_default",
 *      routeView="oro_calendar_event_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-clock-o"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          },
 *          "grouping"={
 *              "groups"={"activity"}
 *          },
 *          "reminder"={
 *              "reminder_template_name"="calendar_reminder",
 *              "reminder_flash_template_identifier"="calendar_event_template"
 *          },
 *          "activity"={
 *              "route"="oro_calendar_event_activity_view",
 *              "acl"="oro_calendar_view",
 *              "action_button_widget"="oro_add_calendar_event_button",
 *              "action_link_widget"="oro_add_calendar_event_link"
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          },
 *          "grid"={
 *              "default"="calendar-event-grid",
 *              "context"="calendar-event-for-context-grid"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CalendarEvent implements
    RemindableInterface,
    DatesAwareInterface,
    AuditAdditionalFieldsInterface,
    ActivityInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendActivity;
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="string", nullable=true, length=36)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $uid;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="parent", orphanRemoval=true, cascade={"all"})
     */
    protected $childEvents;

    /**
     * @var CalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="CalendarEvent", inversedBy="childEvents", fetch="EAGER")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var Calendar
     *
     * @ORM\ManyToOne(targetEntity="Calendar", inversedBy="events")
     * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $calendar;

    /**
     * @var SystemCalendar
     *
     * @ORM\ManyToOne(targetEntity="SystemCalendar", inversedBy="events")
     * @ORM\JoinColumn(name="system_calendar_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $systemCalendar;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $end;

    /**
     * @var bool
     *
     * @ORM\Column(name="all_day", type="boolean", nullable=false, options={"default"=false})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $allDay = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="background_color", type="string", length=7, nullable=true)
     */
    protected $backgroundColor;

    /**
     * @var Collection
     */
    protected $reminders;

    /**
     * Contains list of all attendees of the event. This property is empty for all child events and
     * value of the one from parentEvent is used since all (parent, child) events have the same attendees
     * (so there is no need for some synchronization mechanism in case attendees changes).
     *
     * @var Collection|Attendee[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Attendee",
     *     mappedBy="calendarEvent",
     *     cascade={"all"},
     *     orphanRemoval=true,
     *     fetch="EAGER"
     * )
     * @ORM\OrderBy({"displayName"="ASC"})
     */
    protected $attendees;

    /**
     * Attendee associated with this event (one attendee from attendees property having calendar owner in user property)
     * It can be null for parent event in case creator of the event is not among attendees.
     *
     * @var Attendee
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Attendee",
     *     cascade={"persist", "remove"},
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(name="related_attendee_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $relatedAttendee;

    /**
     * Defines recurring event rules. Only original recurring event has this relation not empty.
     *
     * @var Recurrence
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Recurrence",
     *     inversedBy="calendarEvent",
     *     cascade={"ALL"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="recurrence_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     */
    protected $recurrence;

    /**
     * Collection of exceptions of recurring event.
     *
     * Exception event is added if one of the events of recurrence have to have different state.
     * For example recurring event starts at 9 AM on weekdays. But on Wednesday user moved this event to 10AM and
     * on Friday user cancelled this event.
     * In that case there will be 3 entities: 1 for recurring event and 2 for exceptions.
     *
     * Only original recurring event might have this collection not empty.
     * Only exception event uses these properties: $recurringEvent, $originalStart and $cancelled.
     * At the same time exception cannot use these properties: $recurrence, $recurringEventExceptions.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="recurringEvent", cascade={"persist"})
     */
    protected $recurringEventExceptions;

    /**
     * This attribute determines whether an event is an exception and what is original recurring event.
     *
     * Only exception event has this relation not empty.
     *
     * @var CalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="CalendarEvent", inversedBy="recurringEventExceptions")
     * @ORM\JoinColumn(name="recurring_event_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $recurringEvent;

    /**
     * For an instance of exception of $recurringEvent, this is the time at which this event would start according to
     * the recurrence data saved in $recurrence property of $recurringEvent.
     *
     * Only exception event has this value not empty.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="original_start_at", type="datetime", nullable=true)
     */
    protected $originalStart;

    /**
     * For an instance of exception of $recurringEvent, this flag determines if this event is cancelled.
     *
     * Only exception event has this value not empty.
     *
     * @var bool
     *
     * @ORM\Column(name="is_cancelled", type="boolean", nullable=false, options={"default"=false})
     */
    protected $cancelled = false;

    /**
     * System flag to indicate clone method of child event is in progress.
     *
     * @internal
     *
     * @var bool
     */
    protected $childEventCloneInProgress = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_organizer", type="boolean", nullable=true)
     */
    protected $isOrganizer;

    /**
     * @var string
     *
     * @ORM\Column(name="organizer_email", type="string", length=255, nullable=true)
     */
    protected $organizerEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="organizer_display_name", type="string", length=255, nullable=true)
     */
    protected $organizerDisplayName;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="organizer_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $organizerUser;

    /**
     * CalendarEvent constructor.
     */
    public function __construct()
    {
        $this->reminders   = new ArrayCollection();
        $this->childEvents = new ArrayCollection();
        $this->attendees   = new ArrayCollection();
        $this->recurringEventExceptions  = new ArrayCollection();
    }

    /**
     * Gets an calendar event id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     *
     * @return CalendarEvent
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Gets UID of a calendar this event belongs to
     * The calendar UID is a string includes a calendar alias and id in the following format: {alias}_{id}
     *
     * @return string|null
     */
    public function getCalendarUid()
    {
        if ($this->calendar) {
            return sprintf('%s_%d', Calendar::CALENDAR_ALIAS, $this->calendar->getId());
        } elseif ($this->systemCalendar) {
            $alias = $this->systemCalendar->isPublic()
                ? SystemCalendar::PUBLIC_CALENDAR_ALIAS
                : SystemCalendar::CALENDAR_ALIAS;

            return sprintf('%s_%d', $alias, $this->systemCalendar->getId());
        }

        return null;
    }

    /**
     * Gets owning user's calendar
     *
     * @return Calendar|null
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets owning user's calendar
     *
     * @param Calendar $calendar
     *
     * @return CalendarEvent
     */
    public function setCalendar(Calendar $calendar = null)
    {
        $this->calendar = $calendar;
        // unlink an event from system calendar
        if ($calendar && $this->getSystemCalendar()) {
            $this->setSystemCalendar(null);
        }

        return $this;
    }

    /**
     * Returns true if calendar is equal to the calendar in passed instance of calendar event.
     *
     * @param Calendar|null $otherCalendar
     * @return bool
     */
    public function isCalendarEqual(Calendar $otherCalendar = null)
    {
        $actualCalendar = $this->getCalendar();
        return $actualCalendar && $otherCalendar &&
            ($actualCalendar === $otherCalendar || $actualCalendar->getId() == $otherCalendar->getId());
    }

    /**
     * Gets owning system calendar
     *
     * @return SystemCalendar|null
     */
    public function getSystemCalendar()
    {
        return $this->systemCalendar;
    }

    public function isSystemEvent(): bool
    {
        return $this->getSystemCalendar() !== null;
    }

    /**
     * Sets owning system calendar
     *
     * @param SystemCalendar $systemCalendar
     *
     * @return CalendarEvent
     */
    public function setSystemCalendar(SystemCalendar $systemCalendar = null)
    {
        $this->systemCalendar = $systemCalendar;
        // unlink an event from user's calendar
        if ($systemCalendar && $this->getCalendar()) {
            $this->setCalendar(null);
        }

        return $this;
    }

    /**
     * Gets calendar event title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets calendar event title.
     *
     * @param string $title
     *
     * @return CalendarEvent
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets calendar event description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets calendar event description.
     *
     * @param  string $description
     *
     * @return CalendarEvent
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets date/time an event begins.
     */
    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    /**
     * Sets date/time an event begins.
     *
     * @param \DateTime|null $start
     *
     * @return CalendarEvent
     */
    public function setStart(\DateTime $start = null)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Gets date/time an event ends.
     *
     * If an event is all-day the end date is inclusive.
     * This means an event with start Nov 10 and end Nov 12 will span 3 days on the calendar.
     *
     * If an event is NOT all-day the end date is exclusive.
     * This is only a gotcha when your end has time 00:00. It means your event ends on midnight,
     * and it will not span through the next day.
     */
    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    /**
     * Sets date/time an event ends.
     *
     * @param \DateTime|null $end
     *
     * @return CalendarEvent
     */
    public function setEnd(\DateTime $end = null)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Indicates whether an event occurs at a specific time-of-day.
     *
     * @return bool
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * Sets a flag indicates whether an event occurs at a specific time-of-day.
     *
     * @param bool $allDay
     *
     * @return CalendarEvent
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Gets a background color of this events.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, e.g. #FF0000.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of this events.
     *
     * @param string|null $backgroundColor The color in hex format, e.g. #FF0000.
     *                                     Set it to null to allow UI to calculate the background color automatically.
     *
     * @return CalendarEvent
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * {@inheritdoc}
     */
    public function setReminders(Collection $reminders)
    {
        $this->reminders = $reminders;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotUserCalendarEvent
     */
    public function getReminderData()
    {
        if (!$this->getCalendar()) {
            throw new NotUserCalendarEvent($this->id);
        }

        $result = new ReminderData();
        $result->setSubject($this->getTitle());
        $result->setExpireAt($this->getStart());
        $result->setRecipient($this->getCalendar()->getOwner());

        return $result;
    }

    /**
     * Get child calendar events
     *
     * @return Collection|CalendarEvent[]
     */
    public function getChildEvents()
    {
        return $this->childEvents;
    }

    /**
     * Set children calendar events.
     *
     * @param Collection|CalendarEvent[] $calendarEvents
     *
     * @return CalendarEvent
     */
    public function resetChildEvents($calendarEvents)
    {
        $this->childEvents->clear();

        foreach ($calendarEvents as $calendarEvent) {
            $this->addChildEvent($calendarEvent);
        }

        return $this;
    }

    /**
     * Add child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function addChildEvent(CalendarEvent $calendarEvent)
    {
        if (!$this->childEvents->contains($calendarEvent)) {
            $this->childEvents->add($calendarEvent);
            $calendarEvent->setParent($this);
        }

        return $this;
    }

    /**
     * Remove child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function removeChildEvent(CalendarEvent $calendarEvent)
    {
        if ($this->childEvents->contains($calendarEvent)) {
            $this->childEvents->removeElement($calendarEvent);
            $calendarEvent->setParent(null);
        }

        return $this;
    }

    /**
     * @param Calendar $calendar
     *
     * @return CalendarEvent|null
     */
    public function getChildEventByCalendar(Calendar $calendar)
    {
        if (!$calendar) {
            return null;
        }

        $result = $this->childEvents->filter(
            function (CalendarEvent $child) use ($calendar) {
                return $child->isCalendarEqual($calendar);
            }
        );

        return $result->count() ? $result->first() : null;
    }

    /**
     * Returns this event or one its' children where related attendee is matching the given instance.
     *
     * @param Attendee $attendee
     *
     * @return CalendarEvent|null
     */
    public function getEventByRelatedAttendee(Attendee $attendee)
    {
        if ($this->getRelatedAttendee() && $this->getRelatedAttendee()->isEqual($attendee)) {
            return $this;
        }

        foreach ($this->getChildEvents() as $childEvent) {
            $childRelatedAttendee = $childEvent->getRelatedAttendee() ?: $childEvent->findRelatedAttendee();
            if ($attendee->isEqual($childRelatedAttendee)) {
                return $childEvent;
            }
        }

        return null;
    }

    /**
     * Set parent calendar event.
     *
     * @param CalendarEvent $parent
     *
     * @return CalendarEvent
     */
    public function setParent(CalendarEvent $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent calendar event.
     *
     * @return CalendarEvent|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns invitation status of the event based on related attendee. If there is no related attendee then returns
     * "none" status (@see Attendee::STATUS_NONE).
     *
     * @see CalendarEvent::getRelatedAttendee()
     *
     * @return string Status id (@see Attendee::STATUS_*)
     */
    public function getInvitationStatus()
    {
        $relatedAttendee = $this->getRelatedAttendee();

        if (!$relatedAttendee) {
            return Attendee::STATUS_NONE;
        }

        return $relatedAttendee->getStatusCode();
    }

    /**
     * Get attendees of Calendar Event. If this event is a child event, the attendees collection will be retrieved
     * from the parent instance.
     *
     * @return Collection|Attendee[]
     */
    public function getAttendees()
    {
        $calendarEvent = $this->getParent() ? : $this;

        return $calendarEvent->attendees;
    }

    /**
     * Get attendee of Calendar Event by email.
     *
     * @param string|null $email If null no return will be returned.
     * @return Attendee|null
     */
    public function getAttendeeByEmail($email)
    {
        if ($email) {
            $attendees = $this->getAttendees();
            foreach ($attendees as $attendee) {
                if ($attendee->isEmailEqual($email)) {
                    return $attendee;
                }
            }
        }

        return null;
    }

    /**
     * Get attendee of Calendar Event by related User of Attendee.
     *
     * @param User $user
     *
     * @return Attendee|null
     */
    public function getAttendeeByUser(User $user)
    {
        $attendees = $this->getAttendees();
        foreach ($attendees as $attendee) {
            if ($attendee->isUserEqual($user)) {
                return $attendee;
            }
        }

        return null;
    }

    /**
     * Get attendee of Calendar Event by Calendar owned by related User of Attendee.
     *
     * @param Calendar $calendar
     *
     * @return Attendee|null
     */
    public function getAttendeeByCalendar(Calendar $calendar)
    {
        return $calendar->getOwner() ? $this->getAttendeeByUser($calendar->getOwner()) : null;
    }

    /**
     * Get attendee of Calendar Event equal to passed instance of attendee.
     *
     * @param Attendee $attendee
     * @return Attendee|null
     */
    public function getEqualAttendee(Attendee $attendee)
    {
        $attendees = $this->getAttendees();
        foreach ($attendees as $actualAttendee) {
            if ($attendee->isEqual($actualAttendee)) {
                return $actualAttendee;
            }
        }

        return null;
    }

    /**
     * Returns all attendees related to child events. This method should not be called using child event.
     *
     * @return Collection|Attendee[]
     * @throws \LogicException If method is called with child event.
     */
    public function getChildAttendees()
    {
        $this->ensureCalendarEventIsNotChild();

        $relatedAttendee = $this->getRelatedAttendee();

        if (!$relatedAttendee) {
            return $this->getAttendees();
        }

        // Filter out related attendee using email
        return $this->getAttendees()->filter(
            function (Attendee $attendee) use ($relatedAttendee) {
                return $attendee !== $relatedAttendee && !$attendee->isEmailEqual($relatedAttendee->getEmail());
            }
        );
    }

    /**
     * Gets recurring event exceptions.
     *
     * @return Collection|CalendarEvent[]
     */
    public function getRecurringEventExceptions()
    {
        return $this->recurringEventExceptions;
    }

    /**
     * Resets recurring event exceptions.
     *
     * @param Collection|CalendarEvent[] $calendarEvents
     *
     * @return CalendarEvent
     */
    public function resetRecurringEventExceptions($calendarEvents)
    {
        $this->recurringEventExceptions->clear();

        foreach ($calendarEvents as $calendarEvent) {
            $this->addRecurringEventException($calendarEvent);
        }

        return $this;
    }

    /**
     * Adds recurring event exception.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function addRecurringEventException(CalendarEvent $calendarEvent)
    {
        if (!$this->recurringEventExceptions->contains($calendarEvent)) {
            $this->recurringEventExceptions->add($calendarEvent);
            $calendarEvent->setRecurringEvent($this);
        }

        return $this;
    }

    /**
     * Removes recurring event exception.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function removeRecurringEventException(CalendarEvent $calendarEvent)
    {
        if ($this->recurringEventExceptions->contains($calendarEvent)) {
            $this->recurringEventExceptions->removeElement($calendarEvent);
            $calendarEvent->setRecurringEvent(null);
        }

        return $this;
    }

    /**
     * Sets parent for calendar event exception.
     *
     * @param CalendarEvent|null $recurringEvent
     *
     * @return CalendarEvent
     */
    public function setRecurringEvent(CalendarEvent $recurringEvent = null)
    {
        $this->recurringEvent = $recurringEvent;

        return $this;
    }

    /**
     * Gets recurring event for calendar event exception.
     *
     * @return CalendarEvent|null
     */
    public function getRecurringEvent()
    {
        return $this->recurringEvent;
    }

    /**
     * Gets originalStart of calendar event exception or null if calendar event is not an exception.
     *
     * @return \DateTime|null
     */
    public function getOriginalStart()
    {
        return $this->originalStart;
    }

    /**
     * Sets originalStart of calendar event exception.
     *
     * @param \DateTime|null $originalStart
     *
     * @return CalendarEvent
     */
    public function setOriginalStart(\DateTime $originalStart = null)
    {
        $this->originalStart = $originalStart;

        return $this;
    }

    /**
     * Sets cancelled flag.
     *
     * @param bool $cancelled
     *
     * @return CalendarEvent
     */
    public function setCancelled($cancelled = false)
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    /**
     * Gets cancelled flag.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->cancelled;
    }

    /**
     * Get attendee of Calendar Event. This method should not be called using child event.
     *
     * @param Collection|Attendee[] $attendees
     * @return CalendarEvent
     * @throws \LogicException If method is called with child event.
     */
    public function setAttendees(Collection $attendees)
    {
        $this->ensureCalendarEventIsNotChild();

        $this->attendees = $attendees;

        return $this;
    }

    /**
     * Throws an exception of Calendar Event has parent. Can be used to restrict calls for some methods using
     * child Calendar Events.
     *
     * @throws \LogicException
     */
    protected function ensureCalendarEventIsNotChild()
    {
        if ($this->getParent()) {
            throw new \LogicException(
                sprintf(
                    'Update of child Calendar Event (id=%d) is restricted. Use parent Calendar Event instead.',
                    $this->getId()
                )
            );
        }
    }

    /**
     * Add attendee of Calendar Event. This method should not be called using child event.
     *
     * @param Attendee $attendee
     * @return CalendarEvent
     * @throws \LogicException If method is called with child event.
     */
    public function addAttendee(Attendee $attendee)
    {
        $this->ensureCalendarEventIsNotChild();

        if (!$this->getAttendees()->contains($attendee)) {
            $attendee->setCalendarEvent($this);
            $this->getAttendees()->add($attendee);
        }

        return $this;
    }

    /**
     * Remove attendee. This method should not be called using child event.
     *
     * @param Attendee $attendee
     * @return CalendarEvent
     * @throws \LogicException If method is called with child event.
     */
    public function removeAttendee(Attendee $attendee)
    {
        $this->ensureCalendarEventIsNotChild();

        if ($this->getAttendees()->contains($attendee)) {
            $this->getAttendees()->removeElement($attendee);
        }

        return $this;
    }

    /**
     * @param Attendee $attendee
     *
     * @return CalendarEvent|null
     */
    public function getChildEventByAttendee(Attendee $attendee)
    {
        $result = $this->getChildEvents()->filter(
            function (CalendarEvent $childEvent) use ($attendee) {
                $calendar = $childEvent->getCalendar();
                $ownerUser = $calendar ? $calendar->getOwner() : null;
                return $ownerUser && $attendee->isUserEqual($ownerUser);
            }
        );

        return $result->count() ? $result->first() : null;
    }

    /**
     * Find attendee related to this event. Related attendee has a user same as an owner of the calendar event.
     *
     * @return Attendee|null
     */
    public function findRelatedAttendee()
    {
        $result = null;

        $calendar = $this->getCalendar();
        if (!$calendar) {
            return $result;
        }

        $ownerUser = $calendar->getOwner();
        if (!$ownerUser) {
            return $result;
        }

        foreach ($this->getAttendees() as $attendee) {
            if ($attendee->isUserEqual($ownerUser)) {
                $result = $attendee;
                break;
            }
        }

        return $result;
    }

    /**
     * @return Attendee
     */
    public function getRelatedAttendee()
    {
        return $this->relatedAttendee;
    }

    /**
     * Returns id of user of related attendee if it exist.
     *
     * @return integer|null
     */
    public function getRelatedAttendeeUserId()
    {
        return $this->getRelatedAttendee() && $this->getRelatedAttendee()->getUser() ?
            $this->getRelatedAttendee()->getUser()->getId() : null;
    }

    /**
     * Returns true if related attendee user is equal to passed instance of user.
     *
     * @param User|null $otherUser
     * @return bool
     */
    public function isRelatedAttendeeUserEqual(User $otherUser = null)
    {
        return $otherUser && $this->getRelatedAttendee() && $this->getRelatedAttendee()->isUserEqual($otherUser);
    }

    /**
     * @param Attendee|null $relatedAttendee
     *
     * @return CalendarEvent
     */
    public function setRelatedAttendee(Attendee $relatedAttendee = null)
    {
        $this->relatedAttendee = $relatedAttendee;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }

    /**
     * Sets recurrence.
     *
     * @param Recurrence|null $recurrence
     *
     * @return CalendarEvent
     */
    public function setRecurrence(Recurrence $recurrence = null)
    {
        $this->recurrence = $recurrence;

        return $this;
    }

    /**
     * Gets recurrence.
     *
     * @return Recurrence|null
     */
    public function getRecurrence()
    {
        return $this->recurrence;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalFields()
    {
        return [
            'uid' => $this->getUid(),
            'calendar_id' => $this->getCalendar() ? $this->getCalendar()->getId() : null,
        ];
    }

    /**
     * Planned for refactoring and will be moved to separate service
     * or another approach should be used to get the previous state of the event
     *
     * The implementation should provides possibility to:
     * - Compare main relations of event with original state before update.
     * - Get access to previous state of the event in email notifications.
     *
     * @see \Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler::process
     * @see \Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler::process
     * @see \Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarEventHandler::process
     * @see \Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationProcessor::sendUpdateParentEventNotification
     */
    public function __clone()
    {
        $this->reminders = new ArrayCollection($this->reminders->toArray());

        // To avoid recursion do not clone parent for child event since it is already a clone
        if ($this->parent && !$this->childEventCloneInProgress) {
            $this->parent = clone $this->parent;
        }

        $this->childEvents = $this->cloneChildEvents($this->childEvents, $this);
        $this->attendees = $this->cloneCollection($this->attendees);
        $this->recurringEventExceptions = $this->cloneCollection($this->recurringEventExceptions);

        if ($this->recurrence) {
            $this->recurrence = clone $this->recurrence;
        }

        $this->childEventCloneInProgress = false;
    }

    /**
     * Clone collection of child events with all elements.
     *
     * @param Collection|CalendarEvent[] $collection
     * @param CalendarEvent $parent
     * @return Collection
     */
    protected function cloneChildEvents(Collection $collection, CalendarEvent $parent)
    {
        $result = new ArrayCollection();

        foreach ($collection as $key => $item) {
            $item->childEventCloneInProgress = true;
            $clonedItem = clone $item;
            $result->set($key, $clonedItem);
            $clonedItem->parent = $parent;
            $item->childEventCloneInProgress = false;
        }

        return $result;
    }

    /**
     * Clone collection, each element of the collection is cloned separately.
     *
     * @param Collection|CalendarEvent[] $collection
     * @return Collection
     */
    protected function cloneCollection(Collection $collection)
    {
        $result = new ArrayCollection();

        foreach ($collection as $key => $item) {
            $clonedItem = clone $item;
            $result->set($key, $clonedItem);
        }

        return $result;
    }

    /**
     * Compares attendees of the event with other event.
     *
     * @param CalendarEvent $other
     *
     * @return bool
     */
    public function hasEqualAttendees(CalendarEvent $other)
    {
        /** @var Attendee[] $actualAttendees */
        $actualAttendees = $this->getAttendees()->toArray();

        /** @var Attendee[] $otherAttendees */
        $otherAttendees = $other->getAttendees()->toArray();

        if (count($actualAttendees) !== count($otherAttendees)) {
            return false;
        }

        $this->sortAttendees($actualAttendees);
        $this->sortAttendees($otherAttendees);

        foreach ($actualAttendees as $key => $originalAttendee) {
            if (!$originalAttendee->isEqual($otherAttendees[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sorts array of attendees according to its email.
     *
     * @param Attendee[] $attendees
     */
    protected function sortAttendees(array &$attendees)
    {
        usort($attendees, function ($attendee1, $attendee2) {
            return strcmp($attendee1->getEmail(), $attendee2->getEmail());
        });
    }

    /**
     * @return bool|null
     */
    public function isOrganizer()
    {
        return $this->isOrganizer;
    }

    /**
     * @param bool $isOrganizer
     *
     * @return CalendarEvent
     */
    public function setIsOrganizer(bool $isOrganizer)
    {
        $this->isOrganizer = $isOrganizer;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getOrganizerUser()
    {
        return $this->organizerUser;
    }

    /**
     * @param User $organizerUser
     *
     * @return CalendarEvent
     */
    public function setOrganizerUser(User $organizerUser)
    {
        $this->organizerUser = $organizerUser;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrganizerDisplayName()
    {
        return $this->organizerDisplayName;
    }

    /**
     * @param string $organizerDisplayName
     *
     * @return CalendarEvent
     */
    public function setOrganizerDisplayName(string $organizerDisplayName)
    {
        $this->organizerDisplayName = $organizerDisplayName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOrganizerEmail()
    {
        return $this->organizerEmail;
    }

    /**
     * @param string $organizerEmail
     *
     * @return CalendarEvent
     */
    public function setOrganizerEmail(string $organizerEmail)
    {
        $this->organizerEmail = $organizerEmail;

        return $this;
    }

    /**
     * This method should be used to calculate (by provided organizer email) "isOrganizer" property.
     * This property is for technical reasons to easily access information "is calendar owner an organizer or not".
     * Without this field, we would need to join additional tables to access that information.
     */
    public function calculateIsOrganizer()
    {
        if ($this->isSystemEvent() || $this->getCalendar() === null) {
            return;
        }

        $owner = $this->getCalendar()->getOwner();
        $organizerEmail = $this->getOrganizerEmail();

        // no organizerEmail passed or organizerEmail is the same as calendar owner
        if ($organizerEmail === null || $organizerEmail === $owner->getEmail()) {
            $this->ownerIsOrganizer();
        } else {
            $this->setIsOrganizer(false);
        }
    }

    private function ownerIsOrganizer()
    {
        if ($this->isSystemEvent()) {
            return;
        }

        $owner = $this->getCalendar()->getOwner();

        $this->setIsOrganizer(true);
        $this->setOrganizerUser($owner);
        $this->setOrganizerEmail($owner->getEmail());

        if (!$this->getOrganizerDisplayName()) {
            $this->setOrganizerDisplayName($owner->getFullName());
        }
    }
}
