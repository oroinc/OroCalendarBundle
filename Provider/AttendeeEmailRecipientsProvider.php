<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;

/**
 * Provider for email recipient list based on Attendee.
 */
class AttendeeEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    private ManagerRegistry $doctrine;
    private EmailRecipientsHelper $emailRecipientsHelper;

    public function __construct(ManagerRegistry $doctrine, EmailRecipientsHelper $emailRecipientsHelper)
    {
        $this->doctrine = $doctrine;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        return $this->emailRecipientsHelper->plainRecipientsFromResult(
            $this->getAttendeeRepository()->getEmailRecipients(
                $args->getOrganization(),
                $args->getQuery(),
                $args->getLimit()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSection(): string
    {
        return 'oro.calendar.autocomplete.attendees';
    }

    private function getAttendeeRepository(): AttendeeRepository
    {
        return $this->doctrine->getRepository(Attendee::class);
    }
}
