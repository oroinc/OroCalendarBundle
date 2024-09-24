<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
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
        $template = file_get_contents($file['path']);
        $templateContent = EmailTemplate::parseContent($template);
        $existingEmailTemplatesList = $this->getEmailTemplatesList($this->getPreviousEmailsDir());
        $existingTemplate = file_get_contents($existingEmailTemplatesList[$fileName]['path']);
        $existingParsedTemplate = EmailTemplate::parseContent($existingTemplate);
        $existingEmailTemplate = $this->findExistingTemplate($manager, $existingParsedTemplate);
        if ($existingEmailTemplate) {
            $this->updateExistingTemplate($existingEmailTemplate, $templateContent);
        }
    }

    #[\Override]
    protected function updateExistingTemplate(EmailTemplate $emailTemplate, array $template): void
    {
        $emailTemplate->setContent($template['content']);
    }

    #[\Override]
    protected function findExistingTemplate(ObjectManager $manager, array $template): ?EmailTemplate
    {
        if (!isset($template['params']['name'])
            || !isset($template['content'])
        ) {
            return null;
        }
        return $manager->getRepository(EmailTemplate::class)->findOneBy([
            'name' => $template['params']['name'],
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
