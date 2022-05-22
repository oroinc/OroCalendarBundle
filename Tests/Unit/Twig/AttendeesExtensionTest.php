<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Twig\AttendeesExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class AttendeesExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var AttendeesExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add('oro_featuretoggle.checker.feature_checker', $this->featureChecker)
            ->getContainer($this);

        $this->extension = new AttendeesExtension($container);
    }

    public function testIsCalendarMasterFeaturesEnabledWithEnabledInvitations(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'is_attendees_invitation_enabled', []));
    }

    public function testIsCalendarMasterFeaturesEnabledWithDisabledInvitations(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(false);

        self::assertFalse(self::callTwigFunction($this->extension, 'is_attendees_invitation_enabled', []));
    }
}
