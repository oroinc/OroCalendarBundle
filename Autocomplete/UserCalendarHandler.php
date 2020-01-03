<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Autocomplete\UserAclHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Autocomplete search handler for users with ACL access level protection for calendar package
 */
class UserCalendarHandler extends UserAclHandler
{
    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param EntityManager                 $em
     * @param AttachmentManager             $attachmentManager
     * @param string                        $className
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param OwnerTreeProvider             $treeProvider
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param AclHelper                     $aclHelper
     * @param AclVoterInterface             $aclVoter
     */
    public function __construct(
        EntityManager $em,
        AttachmentManager $attachmentManager,
        $className,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $treeProvider,
        EntityRoutingHelper $entityRoutingHelper,
        AclHelper $aclHelper,
        AclVoterInterface $aclVoter = null
    ) {
        parent::__construct(
            $em,
            $attachmentManager,
            $className,
            $authorizationChecker,
            $tokenAccessor,
            $treeProvider,
            $entityRoutingHelper,
            $aclVoter
        );

        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $query
     *
     * @return object|null
     */
    protected function searchById($query)
    {
        return $this->em->getRepository('OroCalendarBundle:Calendar')->findBy(['id' => explode(',', $query)]);
    }

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder()
    {
        return $this->em->createQueryBuilder()
            ->select('calendar, user')
            ->from('OroCalendarBundle:Calendar', 'calendar')
            ->innerJoin('calendar.owner', 'user');
    }

    /**
     * {@inheritdoc}
     */
    protected function applyAcl(QueryBuilder $queryBuilder, $accessLevel, User $user, Organization $organization)
    {
        return $this->aclHelper->apply($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($calendar)
    {
        $result = parent::convertItem($calendar->getOwner());
        $result['id'] = $calendar->getId();
        $result['userId'] = $calendar->getOwner()->getId();

        return $result;
    }
}
