<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Sends email notifications for attendees of the events and calendar event owner.
 *
 * Email categories:
 *
 * 1) Emails sent to all attendees except owner of the event.
 *  Templates:
 *      - calendar_invitation_invite                - Send to all attendees when new event is created.
 *                                                  - Send to new attendees added to the event.
 *      - calendar_invitation_update                - Send to all existed attendees when event is updated.
 *      - calendar_invitation_uninvite              - Send to existed attendees removed from the event.
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
 *      - entity                                    - Calendar event of attendee, e.g. child calendar event.
 */
class EmailNotificationSender
{
    const NOTIFICATION_TEMPLATE_INVITE           = 'calendar_invitation_invite';
    const NOTIFICATION_TEMPLATE_UPDATE           = 'calendar_invitation_update';
    const NOTIFICATION_TEMPLATE_CANCEL           = 'calendar_invitation_delete_parent_event';
    const NOTIFICATION_TEMPLATE_UN_INVITE        = 'calendar_invitation_uninvite';
    const NOTIFICATION_TEMPLATE_STATUS_ACCEPTED  = 'calendar_invitation_accepted';
    const NOTIFICATION_TEMPLATE_STATUS_TENTATIVE = 'calendar_invitation_tentative';
    const NOTIFICATION_TEMPLATE_STATUS_DECLINED  = 'calendar_invitation_declined';
    const NOTIFICATION_TEMPLATE_DELETE_CHILD     = 'calendar_invitation_delete_child_event';

    /**
     * @var EmailNotificationManager
     */
    protected $emailNotificationManager;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var TemplateEmailNotification[]
     */
    protected $emailNotifications = [];

    public function __construct(EmailNotificationManager $emailNotificationManager, ManagerRegistry $managerRegistry)
    {
        $this->emailNotificationManager = $emailNotificationManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Sends all previously added notifications.
     */
    public function sendAddedNotifications()
    {
        if (!$this->emailNotifications) {
            return;
        }

        $this->emailNotificationManager->process($this->emailNotifications);

        $this->emailNotifications = [];
        $this->managerRegistry->getManager()->flush();
    }

    /**
     * Adds invite notification for each passed attendee for the calendar event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    public function addInviteNotifications(CalendarEvent $calendarEvent, array $attendees)
    {
        $this->addAttendeesNotifications(
            $calendarEvent,
            $attendees,
            self::NOTIFICATION_TEMPLATE_INVITE
        );
    }

    /**
     * Add email notifications to the list of attendees except owner of the calendar event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param Attendee[]    $attendees      Attendees without email will be filtered out and no
     *                                      notification will be added for them.
     * @param string $templateName
     */
    protected function addAttendeesNotifications(
        CalendarEvent $calendarEvent,
        array $attendees,
        $templateName
    ) {
        foreach ($attendees as $attendee) {
            if (!$attendee->getEmail()) {
                // Cannot send notification to attendee without an email.
                continue;
            }
            $attendeeEvent = $calendarEvent->getEventByRelatedAttendee($attendee);
            if (!$attendeeEvent) {
                $attendeeEvent = $calendarEvent;
            }
            $this->addEmailNotification(
                $this->createTemplateEmailNotification(
                    $attendeeEvent,
                    $attendee,
                    $templateName
                )
            );
        }
    }

    /**
     * @param CalendarEvent        $calendarEvent  Calendar event of the notification.
     * @param EmailHolderInterface $recipient      Recipient's object
     * @param string               $templateName   Name of template
     * @return TemplateEmailNotification
     */
    private function createTemplateEmailNotification(
        CalendarEvent $calendarEvent,
        EmailHolderInterface $recipient,
        string $templateName
    ): TemplateEmailNotification {
        return new TemplateEmailNotification(new EmailTemplateCriteria($templateName), [$recipient], $calendarEvent);
    }

    /**
     * Adds email notification for further processing with email notification manager..
     */
    protected function addEmailNotification(TemplateEmailNotification $emailNotification)
    {
        $this->emailNotifications[] = $emailNotification;
    }

    /**
     * Adds cancel notification for each passed attendee for the calendar event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    public function addCancelNotifications(CalendarEvent $calendarEvent, array $attendees)
    {
        $this->addAttendeesNotifications(
            $calendarEvent,
            $attendees,
            self::NOTIFICATION_TEMPLATE_CANCEL
        );
    }

    /**
     * Adds un-invite notification for each passed attendee for the calendar event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    public function addUnInviteNotifications(CalendarEvent $calendarEvent, array $attendees)
    {
        $this->addAttendeesNotifications(
            $calendarEvent,
            $attendees,
            self::NOTIFICATION_TEMPLATE_UN_INVITE
        );
    }

    /**
     * Adds update notification for each passed attendee for the calendar event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    public function addUpdateNotifications(CalendarEvent $calendarEvent, array $attendees)
    {
        $this->addAttendeesNotifications(
            $calendarEvent,
            $attendees,
            self::NOTIFICATION_TEMPLATE_UPDATE
        );
    }

    /**
     * Add invitation status change notification for owner of the event.
     *
     * Method is applicable only for child events.
     *
     * Method is applicable for events with existing related attendee.
     *
     * @param CalendarEvent $calendarEvent  The child event where invitation status was updated.
     * @param User          $ownerUser      Owner user of main calendar event.
     * @param string        $statusCode     New code of invitation status.
     *
     * @throws \BadMethodCallException If event has no related attendee or notification status is not supported.
     */
    public function addInvitationStatusChangeNotifications(
        CalendarEvent $calendarEvent,
        User $ownerUser,
        $statusCode
    ) {
        switch ($statusCode) {
            case Attendee::STATUS_ACCEPTED:
                $templateName = self::NOTIFICATION_TEMPLATE_STATUS_ACCEPTED;
                break;
            case Attendee::STATUS_TENTATIVE:
                $templateName = self::NOTIFICATION_TEMPLATE_STATUS_TENTATIVE;
                break;
            case Attendee::STATUS_DECLINED:
                $templateName = self::NOTIFICATION_TEMPLATE_STATUS_DECLINED;
                break;
            default:
                throw new \BadMethodCallException(
                    sprintf('Attendee respond status "%s" is not supported for email notification.', $statusCode)
                );
        }

        $this->addUserEmailNotification($calendarEvent, $ownerUser, $templateName);
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
    protected function addUserEmailNotification(
        CalendarEvent $calendarEvent,
        User $user,
        $templateName
    ) {
        if (!$user->getEmail()) {
            return;
        }

        $this->addEmailNotification(
            $this->createTemplateEmailNotification(
                $calendarEvent,
                $user,
                $templateName
            )
        );
    }

    /**
     * Add child event delete notification for owner of the event.
     *
     * @param CalendarEvent $childCalendarEvent Deleted calendar event of attendee user.
     * @param User          $ownerUser          Owner user of main calendar event.
     */
    public function addDeleteChildCalendarEventNotifications(
        CalendarEvent $childCalendarEvent,
        User $ownerUser
    ) {
        $this->addUserEmailNotification(
            $childCalendarEvent,
            $ownerUser,
            self::NOTIFICATION_TEMPLATE_DELETE_CHILD
        );
    }
}
