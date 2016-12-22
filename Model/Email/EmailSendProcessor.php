<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sends email notifications for attendees of the events and event parent calendar event owner.
 *
 * Email categories:
 *
 * 1) Emails sent to all attendees except owner of the event.
 *  Templates:
 *      - calendar_invitation_invite                - Send to all attendees when new event is created.
 *                                                  - Send to new attendees added to the event.
 *      - calendar_invitation_update                - Send to all existed attendees when event is updated.
 *      - calendar_invitation_uninvite              - Send to existed attendees removed from the the event.
 *      - calendar_invitation_delete_parent_event   - Send to existed attendees when the event is removed.
 *  Available variables in the template:
 *      - entity                                    - Case 1: Child event in the calendar of recipient user.
 *                                                  - Case 2: If recipient is an attendee without related user then
 *                                                            it represents the event in calendar of owner user.
 *
 * 2) Emails sent to owner of the calendar event.
 *  Templates:
 *      - calendar_invitation_accepted              - Send when attendee accepts the event.
 *      - calendar_invitation_declined              - Send when attendee declines the event.
 *      - calendar_invitation_tentative             - Send when attendee tentatively accepts the event.
 *      - calendar_invitation_delete_child_event    - Send when attendee removes the event from own calendar.
 *  Available variables in the template:
 *      - entity                                    - Calendar event of main owner's, e.g. parent event for attendee.
 */
class EmailSendProcessor
{
    const CREATE_INVITE_TEMPLATE_NAME = 'calendar_invitation_invite';
    const UPDATE_INVITE_TEMPLATE_NAME = 'calendar_invitation_update';
    const CANCEL_INVITE_TEMPLATE_NAME = 'calendar_invitation_delete_parent_event';
    const UN_INVITE_TEMPLATE_NAME     = 'calendar_invitation_uninvite';
    const ACCEPTED_TEMPLATE_NAME      = 'calendar_invitation_accepted';
    const TENTATIVE_TEMPLATE_NAME     = 'calendar_invitation_tentative';
    const DECLINED_TEMPLATE_NAME      = 'calendar_invitation_declined';
    const REMOVE_CHILD_TEMPLATE_NAME  = 'calendar_invitation_delete_child_event';

    /**
     * @var EmailNotificationManager
     */
    protected $emailNotificationManager;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var EmailNotification[]
     */
    protected $emailNotifications = [];

    /**
     * @param EmailNotificationManager $emailNotificationManager
     * @param ObjectManager              $objectManager
     */
    public function __construct(EmailNotificationManager $emailNotificationManager, ObjectManager $objectManager)
    {
        $this->emailNotificationManager = $emailNotificationManager;
        $this->entityManager = $objectManager;
    }

    /**
     * Send invitation notification to invitees
     *
     * @param CalendarEvent $calendarEvent
     */
    public function sendInviteNotification(CalendarEvent $calendarEvent)
    {
        $this->addAttendeesEmailNotifications(
            $calendarEvent,
            $calendarEvent->getAttendees()->toArray(),
            $calendarEvent->isCancelled() ? self::CANCEL_INVITE_TEMPLATE_NAME : self::CREATE_INVITE_TEMPLATE_NAME
        );
        $this->process();
    }

    /**
     * Add email notifications to the list of attendees except owner of the calendar event.
     *
     * @param CalendarEvent         $calendarEvent
     * @param Attendee[]            $attendees          Attendees without email will be filtered out and no
     *                                                  notification will be added for them.
     * @param string                $templateName
     */
    protected function addAttendeesEmailNotifications(
        CalendarEvent $calendarEvent,
        array $attendees,
        $templateName
    ) {
        $ownerUser = $this->getCalendarOwnerUser($calendarEvent);

        foreach ($attendees as $attendee) {
            if (!$attendee->getEmail()) {
                // Cannot send notification to attendee without an email.
                continue;
            }
            if ($attendee->isUserEqual($ownerUser)) {
                // Do not send notification to owner of the event.
                continue;
            }
            $attendeeEvent = $calendarEvent->getEventByRelatedAttendee($attendee);
            if (!$attendeeEvent) {
                $attendeeEvent = $calendarEvent;
            }
            $this->addEmailNotification(
                $this->createEmailNotification(
                    $attendeeEvent,
                    $attendee->getEmail(),
                    $templateName
                )
            );
        }
    }

    /**
     * Get email of related attendee of parent event.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return User
     *
     * @throws \LogicException When event has no owner user.
     *
     */
    protected function getCalendarOwnerUser(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getCalendar() || !$calendarEvent->getCalendar()->getOwner()) {
            throw new \LogicException('Calendar event owner user is not accessible.');
        }

        return $calendarEvent->getCalendar()->getOwner();
    }

    /**
     * Adds email notification for further processing with email notification manager..
     *
     * @param EmailNotification $emailNotification
     */
    protected function addEmailNotification(EmailNotification $emailNotification)
    {
        $this->emailNotifications[] = $emailNotification;
    }

    /**
     * Creates email notification model for further processing with email notification manager.
     *
     * @param CalendarEvent $calendarEvent
     * @param string        $email          Recipient's email
     * @param string        $templateName
     *
     * @return EmailNotification
     */
    protected function createEmailNotification(
        CalendarEvent $calendarEvent,
        $email,
        $templateName
    ) {
        $result = new EmailNotification($this->entityManager);
        $result->setEmails([$email]);
        $result->setCalendarEvent($calendarEvent);
        $result->setTemplateName($templateName);

        return $result;
    }

    /**
     * Processes all created email notifications.
     */
    protected function process()
    {
        if (!$this->emailNotifications) {
            return;
        }

        foreach ($this->emailNotifications as $notification) {
            $this->emailNotificationManager->process(
                $notification->getEntity(),
                [$notification]
            );
        }

        $this->emailNotifications = [];
        $this->entityManager->flush();
    }

    /**
     * Send notification to attendees if event was changed
     *
     * @param CalendarEvent $calendarEvent
     * @param CalendarEvent $originalCalendarEvent
     * @param boolean       $notify
     *
     * @return boolean
     */
    public function sendUpdateParentEventNotification(
        CalendarEvent $calendarEvent,
        CalendarEvent $originalCalendarEvent,
        $notify = false
    ) {
        $originalAttendees = $originalCalendarEvent->getAttendees();

        if ($notify) {
            // Send notification when event was changed to attendees which were existed originally.
            $existedAttendees = $this->getExistedAttendees($originalAttendees, $calendarEvent->getAttendees());
            $this->addAttendeesEmailNotifications(
                $calendarEvent,
                $existedAttendees,
                self::UPDATE_INVITE_TEMPLATE_NAME
            );
        }

        // Send notification to new attendees
        $addedAttendees = $this->getAddedAttendees($originalAttendees, $calendarEvent->getAttendees());
        $this->addAttendeesEmailNotifications(
            $calendarEvent,
            $addedAttendees,
            self::CREATE_INVITE_TEMPLATE_NAME
        );

        // Send notification to attendees removed from the event
        $removedAttendees = $this->getRemovedAttendees($originalAttendees, $calendarEvent->getAttendees());
        $this->addAttendeesEmailNotifications(
            $originalCalendarEvent,
            $removedAttendees,
            self::UN_INVITE_TEMPLATE_NAME
        );

        $this->process();

        return true;
    }

    /**
     * Returns array of attendees which were existed before in $originalAttendees and exist now in $actualAttendees.
     *
     * @param Collection $originalAttendees
     * @param Collection $actualAttendees
     * @return Attendee[]
     */
    protected function getExistedAttendees(Collection $originalAttendees, Collection $actualAttendees)
    {
        $result = [];
        foreach ($actualAttendees as $actualAttendee) {
            if ($originalAttendees->contains($actualAttendee)) {
                $result[] = $actualAttendee;
            }
        }
        return $result;
    }

    /**
     * Returns array of attendees which were not existed before in $originalAttendees and exist now in $actualAttendees.
     *
     * @param Collection $originalAttendees
     * @param Collection $actualAttendees
     * @return Attendee[]
     */
    protected function getAddedAttendees(Collection $originalAttendees, Collection $actualAttendees)
    {
        $result = [];
        foreach ($actualAttendees as $actualAttendee) {
            if (!$originalAttendees->contains($actualAttendee)) {
                $result[] = $actualAttendee;
            }
        }
        return $result;
    }

    /**
     * Returns array of attendees which were not existed before in $originalAttendees and exist now in $actualAttendees.
     *
     * @param Collection $originalAttendees
     * @param Collection $actualAttendees
     * @return Attendee[]
     */
    protected function getRemovedAttendees(Collection $originalAttendees, Collection $actualAttendees)
    {
        $result = [];
        foreach ($originalAttendees as $originalAttendee) {
            if (!$actualAttendees->contains($originalAttendee)) {
                $result[] = $originalAttendee;
            }
        }
        return $result;
    }

    /**
     * Send respond notification to event owner when one of the attendees changed the invitation status.
     *
     * Method is applicable only for child events.
     *
     * Method is applicable for events with existing related attendee.
     *
     * @param CalendarEvent $calendarEvent The child event where invitation status was updated.
     * @throws \BadMethodCallException If event has no related attendee or notification status is not supported.
     */
    public function sendRespondNotification(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getParent()) {
            // Method is applicable only for child events.
            return;
        }

        $relatedAttendee = $calendarEvent->getRelatedAttendee();
        if (!$relatedAttendee) {
            throw new \BadMethodCallException('Method is applicable only for events with existing related attendee.');
        }

        $statusCode = $relatedAttendee->getStatusCode();
        switch ($statusCode) {
            case CalendarEvent::STATUS_ACCEPTED:
                $templateName = self::ACCEPTED_TEMPLATE_NAME;
                break;
            case CalendarEvent::STATUS_TENTATIVE:
                $templateName = self::TENTATIVE_TEMPLATE_NAME;
                break;
            case CalendarEvent::STATUS_DECLINED:
                $templateName = self::DECLINED_TEMPLATE_NAME;
                break;
            default:
                throw new \BadMethodCallException(
                    sprintf('Attendee respond status "%s" is not supported for email notification.', $statusCode)
                );
        }

        $ownerUser = $this->getCalendarOwnerUser($calendarEvent->getParent());

        $this->addUserEmailNotifications($calendarEvent, $ownerUser, $templateName);

        $this->process();
    }

    /**
     * Add email notifications to user.
     *
     * Notification won't be send to user without email.
     *
     * @param CalendarEvent         $calendarEvent
     * @param User                  $user
     * @param string                $templateName
     */
    protected function addUserEmailNotifications(
        CalendarEvent $calendarEvent,
        User $user,
        $templateName
    ) {
        if (!$user->getEmail()) {
            return;
        }

        $this->addEmailNotification(
            $this->createEmailNotification(
                $calendarEvent,
                $user->getEmail(),
                $templateName
            )
        );
    }

    /**
     * Send notification to attendees when event was removed.
     *
     * Send notification to owner of the event when attendee had removed his child event.
     *
     * Method is applicable only for child events.
     *
     * @param CalendarEvent $calendarEvent
     */
    public function sendDeleteEventNotification(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->getParent()) {
            $ownerUser = $this->getCalendarOwnerUser($calendarEvent->getParent());
            // Send notification to owner of the event when attendee had removed his child event.
            $this->addUserEmailNotifications($calendarEvent, $ownerUser, self::REMOVE_CHILD_TEMPLATE_NAME);
        } elseif (count($calendarEvent->getChildAttendees()) > 0) {
            // Send notification to all attendees except current user when event was removed.
            $this->addAttendeesEmailNotifications(
                $calendarEvent,
                $calendarEvent->getAttendees()->toArray(),
                self::CANCEL_INVITE_TEMPLATE_NAME
            );
        }
        $this->process();
    }
}
