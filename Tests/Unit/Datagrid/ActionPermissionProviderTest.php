<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class ActionPermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ADMIN = 1;
    private const USER = 2;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ActionPermissionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->provider = new ActionPermissionProvider($this->tokenAccessor);
    }

    /**
     * @dataProvider permissionsDataProvider
     */
    public function testGetInvitationPermissions(array $params, array $expected)
    {
        $user = new User();
        $user->setId(self::ADMIN);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(
            $expected,
            $this->provider->getInvitationPermissions(new ResultRecord($params))
        );
    }

    public function permissionsDataProvider(): array
    {
        return [
            'invitation child' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => '3512',
                    'ownerId' => self::ADMIN,
                    'relatedAttendeeUserId' => self::ADMIN,
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => true,
                    'tentative'   => true,
                    'view'        => true,
                    'update'      => false
                ]
            ],
            'invitation parent' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => '3512',
                    'ownerId' => self::ADMIN,
                    'relatedAttendeeUserId' => self::ADMIN,
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => true,
                    'tentative'   => true,
                    'view'        => true,
                    'update'      => false
                ]
            ],
            'not invitation' => [
                'params' => [
                    'invitationStatus' => null,
                    'parentId' => null,
                    'ownerId' => self::ADMIN,
                    'relatedAttendeeUserId' => self::ADMIN
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => false,
                    'tentative'   => false,
                    'view'        => true,
                    'update'      => true
                ]
            ],
            'other user invitation' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => '3512',
                    'ownerId' => self::USER,
                    'relatedAttendeeUserId' => self::USER
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => false,
                    'tentative'   => false,
                    'view'        => true,
                    'update'      => false
                ]
            ],
            'without child events' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => null,
                    'ownerId' => self::ADMIN,
                    'relatedAttendeeUserId' => null
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => false,
                    'tentative'   => false,
                    'view'        => true,
                    'update'      => true
                ]
            ]
        ];
    }
}
