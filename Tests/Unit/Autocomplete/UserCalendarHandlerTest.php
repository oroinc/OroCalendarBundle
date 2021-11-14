<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CalendarBundle\Autocomplete\UserCalendarHandler;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserCalendarHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var OwnerTreeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $treeProvider;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var UserCalendarHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->treeProvider = $this->createMock(OwnerTreeProvider::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);

        $this->handler = new UserCalendarHandler(
            $this->em,
            $this->attachmentManager,
            UserCalendarHandler::class,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->treeProvider,
            $this->entityRoutingHelper,
            $this->aclHelper
        );
        $this->handler->setEntityNameResolver($this->entityNameResolver);
        $this->handler->setProperties([
            'avatar',
            'firstName',
            'fullName',
            'id',
            'lastName',
            'middleName',
            'namePrefix',
            'nameSuffix',
        ]);
    }

    public function testConvertItem()
    {
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $user->setFirstName('testFirstName');
        $user->setLastName('testLastName');

        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 2);
        $calendar->setOwner($user);

        $result = $this->handler->convertItem($calendar);
        $this->assertEquals($result['id'], $calendar->getId());
        $this->assertEquals($result['userId'], $calendar->getOwner()->getId());
    }
}
