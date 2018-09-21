<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Model\Email\TemplateEmailNotification;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;

class TemplateEmailNotificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $em;

    /**
     * @var TemplateEmailNotification
     */
    private $notification;

    protected function setUp()
    {
        $this->em = $this->createMock(ObjectManager::class);
        $this->notification = new TemplateEmailNotification($this->em);
    }

    public function testGetTemplateConditions()
    {
        $templateName = 'template_name';
        $this->notification->setTemplateName($templateName);
        $this->assertEquals(
            new EmailTemplateCriteria($templateName, TemplateEmailNotification::ENTITY_CLASS_NAME),
            $this->notification->getTemplateCriteria()
        );
    }

    public function testGetRecipients()
    {
        $recipient = new EmailHolderStub();
        $this->notification->addRecipient($recipient);
        $this->assertSame([$recipient], $this->notification->getRecipients());
    }
}
