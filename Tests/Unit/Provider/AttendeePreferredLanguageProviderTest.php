<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Provider\AttendeePreferredLanguageProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\Provider\EmailAddressWithContextPreferredLanguageProvider;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeePreferredLanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    private const EMAIL_ADDRESS = 'some@mail.com';

    private const LANGUAGE = 'fr_FR';

    /**
     * @var PreferredLanguageProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $chainLanguageProvider;

    /**
     * @var EmailAddressWithContextPreferredLanguageProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->chainLanguageProvider = $this->createMock(PreferredLanguageProviderInterface::class);
        $this->provider = new AttendeePreferredLanguageProvider($this->chainLanguageProvider);
    }

    public function testSupports(): void
    {
        self::assertTrue($this->provider->supports(new Attendee()));
    }

    public function testSupportsFail(): void
    {
        self::assertFalse($this->provider->supports(new \stdClass()));
    }

    public function testGetPreferredLanguageWhenNotSupports(): void
    {
        $this->expectException(\LogicException::class);

        $this->provider->getPreferredLanguage(new \stdClass());
    }

    /**
     * @dataProvider userDataProvider
     * @param User|null $user
     */
    public function testGetPreferredLanguage(User $user = null): void
    {
        $attendee = (new Attendee())->setEmail(self::EMAIL_ADDRESS)->setUser($user);

        $this->chainLanguageProvider
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($user)
            ->willReturn(self::LANGUAGE);

        self::assertEquals(self::LANGUAGE, $this->provider->getPreferredLanguage($attendee));
    }

    /**
     * @return array
     */
    public function userDataProvider(): array
    {
        return [
            'related user exists' => [
                'user' => new User(),
            ],
            'no related user exists' => [
                'user' => null
            ]
        ];
    }
}
