<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;

/**
 * Provides possibility to get calendar notification info such as email template conditions and recipient objects
 */
class TemplateEmailNotification extends EmailNotification implements TemplateEmailNotificationInterface
{
    /**
     * @var iterable|EmailHolderInterface[]
     */
    private $recipients = [];

    /**
     * {@inheritdoc}
     */
    public function getTemplateCriteria(): EmailTemplateCriteria
    {
        return new EmailTemplateCriteria($this->templateName, static::ENTITY_CLASS_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(): iterable
    {
        return $this->recipients;
    }

    /**
     * @param EmailHolderInterface $recipient
     * @return $this
     */
    public function addRecipient(EmailHolderInterface $recipient)
    {
        $this->recipients[] = $recipient;

        return $this;
    }
}
