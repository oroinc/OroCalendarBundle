<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ChildEventsSubscriber implements EventSubscriberInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /** @var AttendeeManager */
    protected $attendeeManager;

    /**
     * ChildEventsSubscriber constructor.
     *
     * @param ManagerRegistry $registry
     * @param CalendarEventManager $calendarEventManager
     * @param AttendeeManager $attendeeManager
     */
    public function __construct(
        ManagerRegistry $registry,
        CalendarEventManager $calendarEventManager,
        AttendeeManager $attendeeManager
    ) {
        $this->registry = $registry;
        $this->calendarEventManager = $calendarEventManager;
        $this->attendeeManager = $attendeeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT  => 'preSubmit',
            FormEvents::POST_SUBMIT => 'postSubmit', // synchronize child events
        ];
    }

    /**
     * We check if there is wrong value in attendee type and set it to null
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (!empty($data['attendees']) && is_array($data['attendees'])) {
            $attendees = &$data['attendees'];

            foreach ($attendees as &$attendee) {
                $type = array_key_exists('type', $attendee) ? $attendee['type'] : null;

                if ($this->shouldTypeBeChecked($type)) {
                    $attendee['type'] = $this->getTypeEnum($type);
                }
            }

            $event->setData($data);
        }
    }

    /**
     * - creates/removes calendar events based on attendee changes
     * - makes sure displayName is not empty
     * - sets default attendee status
     * - updates duplicated values of child events
     * (It would be better to have separate entity for common data. Could be e.g. CalendarEventInfo)
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var CalendarEvent $calendarEvent */
        $calendarEvent = $event->getForm()->getData();
        if (!$calendarEvent) {
            return;
        }

        $this->calendarEventManager->updateCalendarEvents($calendarEvent);

        $this->attendeeManager->updateAttendeeDisplayNames($calendarEvent->getAttendees());

        $calendarEvent->updateChildEvents();

        $this->attendeeManager->setDefaultAttendeeStatus(
            $calendarEvent->getAttendees(),
            $calendarEvent->findRelatedAttendee()
        );
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function shouldTypeBeChecked($type)
    {
        return
            null !== $type
            && !in_array($type, [Attendee::TYPE_OPTIONAL, Attendee::TYPE_REQUIRED, Attendee::TYPE_ORGANIZER]);
    }

    /**
     * @param $type
     *
     * @return object
     */
    protected function getTypeEnum($type)
    {
        return $this->registry
            ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::TYPE_ENUM_CODE))
            ->find($type);
    }
}
