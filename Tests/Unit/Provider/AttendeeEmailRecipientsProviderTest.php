<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Provider\AttendeeEmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;

class AttendeeEmailRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttendeeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeeRepository;

    /** @var EmailRecipientsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRecipientsHelper;

    /** @var AttendeeEmailRecipientsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->attendeeRepository = $this->createMock(AttendeeRepository::class);
        $this->emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Attendee::class)
            ->willReturn($this->attendeeRepository);

        $this->provider = new AttendeeEmailRecipientsProvider(
            $doctrine,
            $this->emailRecipientsHelper
        );
    }

    public function testGetSection()
    {
        $this->assertEquals('oro.calendar.autocomplete.attendees', $this->provider->getSection());
    }

    public function testGetRecipients()
    {
        $args = new EmailRecipientsProviderArgs(null, 'query', 100);

        $this->attendeeRepository->expects($this->once())
            ->method('getEmailRecipients')
            ->with(null, 'query', 100)
            ->willReturn([]);

        $this->emailRecipientsHelper->expects($this->once())
            ->method('plainRecipientsFromResult')
            ->with([])
            ->willReturn([]);

        $this->assertEquals([], $this->provider->getRecipients($args));
    }
}
