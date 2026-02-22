<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Twig\AttendeesExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttendeesExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private FeatureChecker&MockObject $featureChecker;
    private AttendeesExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add(FeatureChecker::class, $this->featureChecker)
            ->getContainer($this);

        $this->extension = new AttendeesExtension($container);
    }

    public function testIsCalendarMasterFeaturesEnabledWithEnabledInvitations(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'is_attendees_invitation_enabled', [])
        );
    }

    public function testIsCalendarMasterFeaturesEnabledWithDisabledInvitations(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(false);

        self::assertFalse(
            self::callTwigFunction($this->extension, 'is_attendees_invitation_enabled', [])
        );
    }
}
