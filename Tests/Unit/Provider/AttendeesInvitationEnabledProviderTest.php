<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\AttendeesInvitationEnabledProvider;
use Oro\Bundle\CalendarBundle\Provider\AttendeesInvitationEnabledProviderInterface;

class AttendeesInvitationEnabledProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testIsAttendeesInvitationEnabledWithoutAdditionalProviders(): void
    {
        $provider = new AttendeesInvitationEnabledProvider([]);

        self::assertTrue($provider->isAttendeesInvitationEnabled());
    }

    public function testIsAttendeesInvitationEnabledWhenAllAdditionalProvidersReturnsTrue(): void
    {
        $provider1 = $this->createMock(AttendeesInvitationEnabledProviderInterface::class);
        $provider1->expects(self::once())
            ->method('isAttendeesInvitationEnabled')
            ->willReturn(true);
        $provider2 = $this->createMock(AttendeesInvitationEnabledProviderInterface::class);
        $provider2->expects(self::once())
            ->method('isAttendeesInvitationEnabled')
            ->willReturn(true);

        $provider = new AttendeesInvitationEnabledProvider([$provider1, $provider2]);

        self::assertTrue($provider->isAttendeesInvitationEnabled());
    }

    public function testIsAttendeesInvitationEnabledWhenOneOfAdditionalProvidersReturnsFalse(): void
    {
        $provider1 = $this->createMock(AttendeesInvitationEnabledProviderInterface::class);
        $provider1->expects(self::once())
            ->method('isAttendeesInvitationEnabled')
            ->willReturn(true);
        $provider2 = $this->createMock(AttendeesInvitationEnabledProviderInterface::class);
        $provider2->expects(self::once())
            ->method('isAttendeesInvitationEnabled')
            ->willReturn(false);

        $provider = new AttendeesInvitationEnabledProvider([$provider1, $provider2]);

        self::assertFalse($provider->isAttendeesInvitationEnabled());
    }
}
