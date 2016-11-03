<?php

namespace Oro\Bundle\CalendarBundle\Test\Context;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * @internal
 */
interface ContextInterface
{
    /**
     * Refresh all references in the context to pull updated from the persistence.
     *
     * @return mixed
     */
    public function refreshReferences();

    /**
     * Get reference from repository.
     *
     * @return mixed
     */
    public function getReference($name);

    /**
     * Set reference object to repository.
     *
     * @param string $name
     * @param mixed $object
     */
    public function setReference($name, $object);

    /**
     * Add reference object to repository.
     *
     * @param string $name
     * @param mixed $object
     */
    public function addReference($name, $object);

    /**
     * Gets an instance of Router.
     *
     * @return RouterInterface
     */
    public function getRouter();

    /**
     * Get instance of Doctrine's manager registry.
     *
     * @return ManagerRegistry
     */
    public function getDoctrine();

    /**
     * Get instance of Doctrine's entity repository.
     *
     * @param string $entityName
     * @return EntityRepository
     */
    public function getEntityRepository($entityName);

    /**
     * @return Client
     */
    public function getClient();

    /**
     * Get an instance of the dependency injection container.
     *
     * @return ContainerInterface
     */
    public function getContainer();
}
