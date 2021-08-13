<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Provider\AttendeesInvitationEnabledProvider;
use Oro\Bundle\CalendarBundle\Twig\AttendeesExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class AttendeesExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var AttendeesInvitationEnabledProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeesInvitationEnabledProvider;

    /** @var AttendeesExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->attendeesInvitationEnabledProvider = $this->createMock(AttendeesInvitationEnabledProvider::class);

        $container = self::getContainerBuilder()
            ->add(
                'oro_calendar.provider.attendees_invitations_enabled_provider',
                $this->attendeesInvitationEnabledProvider
            )
            ->getContainer($this);

        $this->extension = new AttendeesExtension($container);
    }

    public function testIsAttendeesInvitationEnabledWithEnabledInvitations(): void
    {
        $this->attendeesInvitationEnabledProvider->expects(self::once())
            ->method('isAttendeesInvitationEnabled')
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'is_attendees_invitation_enabled', []));
    }

    public function testIsAttendeesInvitationEnabledWithDisabledInvitations(): void
    {
        $this->attendeesInvitationEnabledProvider->expects(self::once())
            ->method('isAttendeesInvitationEnabled')
            ->willReturn(false);

        self::assertFalse(self::callTwigFunction($this->extension, 'is_attendees_invitation_enabled', []));
    }
}
