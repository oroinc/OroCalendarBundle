<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateRawDataParser;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;

/**
 * Added html tag around twig tags
 * Allows to edit text from WYSIWYG editor and does not break the twig template
 */
class ConvertCalendarInvitationEmail extends AbstractEmailFixture
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadEmailTemplates::class];
    }

    #[\Override]
    public function getEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/invitation');
    }

    #[\Override]
    protected function loadTemplate(ObjectManager $manager, $fileName, array $file): void
    {
        /** @var EmailTemplateRawDataParser $emailTemplateRawDataParser */
        $emailTemplateRawDataParser = $this->container->get('oro_email.email_template_hydrator.raw_data_parser');

        $newTemplateRawData = file_get_contents($file['path']);
        $newTemplateArrayData = $emailTemplateRawDataParser->parseRawData($newTemplateRawData);

        $existingEmailTemplatesList = $this->getEmailTemplatesList($this->getPreviousEmailsDir());
        $existingTemplateRawData = file_get_contents($existingEmailTemplatesList[$fileName]['path']);
        $existingTemplateArrayData = $emailTemplateRawDataParser->parseRawData($existingTemplateRawData);
        $existingEmailTemplate = $this->findExistingTemplate($manager, $existingTemplateArrayData);
        if ($existingEmailTemplate) {
            $this->updateExistingTemplate($existingEmailTemplate, $newTemplateArrayData);
        }
    }

    #[\Override]
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $arrayData): void
    {
        $emailTemplate->setContent($arrayData['content']);
    }

    #[\Override]
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (!isset($template['name'], $template['content'])) {
            return null;
        }
        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['name'],
            'entityName' => CalendarEvent::class,
            'content' => $template['content']
        ]);
    }

    private function getPreviousEmailsDir(): string
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/v1_0');
    }
}
