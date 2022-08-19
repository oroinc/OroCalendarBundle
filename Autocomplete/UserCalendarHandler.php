<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Autocomplete\UserSearchHandler;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Autocomplete search handler for user calendars.
 */
class UserCalendarHandler implements SearchHandlerInterface
{
    private ManagerRegistry $doctrine;
    private PictureSourcesProviderInterface $pictureSourcesProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private EntityRoutingHelper $entityRoutingHelper;
    private EntityNameResolver $entityNameResolver;
    private AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        PictureSourcesProviderInterface $pictureSourcesProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        EntityRoutingHelper $entityRoutingHelper,
        EntityNameResolver $entityNameResolver,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->pictureSourcesProvider = $pictureSourcesProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        [$search, $entityClass, $permission, $entityId, $excludeCurrentUser] = explode(';', $query);
        $entityClass = $this->entityRoutingHelper->resolveEntityClass($entityClass);

        $hasMore = false;
        $object = $entityId
            ? $this->doctrine->getRepository($entityClass)->find((int)$entityId)
            : ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            if ($searchById) {
                $results = $this->doctrine->getRepository(Calendar::class)->findBy(['id' => explode(',', $search)]);
            } else {
                $page = (int)$page > 0 ? (int)$page : 1;
                $perPage = (int)$perPage > 0 ? (int)$perPage : 10;
                $firstResult = ($page - 1) * $perPage;
                $perPage++;

                $queryBuilder = $this->createQueryBuilder($search);
                if ($excludeCurrentUser) {
                    $queryBuilder
                        ->andWhere('user.id != :userId')
                        ->setParameter('userId', $this->tokenAccessor->getUser()->getId());
                }
                $queryBuilder
                    ->setFirstResult($firstResult)
                    ->setMaxResults($perPage);
                $results = $this->aclHelper->apply($queryBuilder)->getResult();

                $hasMore = count($results) === $perPage;
                if ($hasMore) {
                    $results = \array_slice($results, 0, $perPage - 1);
                }
            }

            $resultsData = [];
            foreach ($results as $item) {
                $resultsData[] = $this->convertItem($item);
            }
        } else {
            $resultsData = [];
        }

        return [
            'results' => $resultsData,
            'more'    => $hasMore
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties()
    {
        return ['id'];
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityName()
    {
        return Calendar::class;
    }

    /**
     * {@inheritDoc}
     */
    public function convertItem($item)
    {
        /** @var Calendar $item */
        $result = [];
        $result['id'] = $item->getId();
        $result['fullName'] = $this->entityNameResolver->getName($item);
        $result['userId'] = $item->getOwner()->getId();
        $result['avatar'] = $this->pictureSourcesProvider->getFilteredPictureSources(
            $item->getOwner()->getAvatar(),
            UserSearchHandler::IMAGINE_AVATAR_FILTER
        );

        return $result;
    }

    private function createQueryBuilder(string $search): QueryBuilder
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Calendar::class);
        $queryBuilder = $em->createQueryBuilder()
            ->select('calendar, user')
            ->from(Calendar::class, 'calendar')
            ->innerJoin('calendar.owner', 'user');
        if ($search) {
            $queryBuilder->where($queryBuilder->expr()->orX(
                'LOWER(CONCAT(user.firstName, CONCAT(\' \', user.lastName))) LIKE :search',
                'LOWER(CONCAT(user.lastName, CONCAT(\' \', user.firstName))) LIKE :search',
                'LOWER(user.username) LIKE :search',
                'LOWER(user.email) LIKE :search'
            ));
            $queryBuilder->setParameter('search', '%' . str_replace(' ', '%', strtolower($search)) . '%');
        }
        $queryBuilder
            ->andWhere('user.enabled = :enabled')
            ->setParameter('enabled', true);

        return $queryBuilder;
    }
}
