<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Provider\AttendeePreferredLocalizationProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeePreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var AttendeePreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PreferredLocalizationProviderInterface::class);

        $this->provider = new AttendeePreferredLocalizationProvider($this->innerProvider);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(object $entity, bool $isSupported): void
    {
        $this->assertSame($isSupported, $this->provider->supports($entity));

        if (!$isSupported) {
            $this->expectException(\LogicException::class);
            $this->provider->getPreferredLocalization($entity);
        }
    }

    public function supportsDataProvider(): array
    {
        return [
            'supported' => [
                'entity' => new Attendee(),
                'isSupported' => true,
            ],
            'not supported' => [
                'entity' => new \stdClass(),
                'isSupported' => false,
            ],
        ];
    }

    public function testGetPreferredLocalization(): void
    {
        $user = new User();
        $entity = (new Attendee())->setUser($user);

        $localization = new Localization();
        $this->innerProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($user))
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
