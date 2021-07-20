<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class ActionPermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    const ADMIN = 1;
    const USER  = 2;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

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
        $record = $this->createMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $user   = new User();
        $user->setId(self::ADMIN);

        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $record->expects($this->at(0))
            ->method('getValue')
            ->with('invitationStatus')
            ->will($this->returnValue($params['invitationStatus']));

        $record->expects($this->at(1))
            ->method('getValue')
            ->with('parentId')
            ->will($this->returnValue($params['parentId']));

        $record->expects($this->at(2))
            ->method('getValue')
            ->with('ownerId')
            ->will($this->returnValue($params['ownerId']));

        $record->expects($this->at(3))
            ->method('getValue')
            ->with('relatedAttendeeUserId')
            ->will($this->returnValue($params['relatedAttendeeUserId']));

        $result = $this->provider->getInvitationPermissions($record);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function permissionsDataProvider()
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
