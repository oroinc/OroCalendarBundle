<?php

namespace Oro\Bundle\CalendarBundle\Test\Context;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\Client;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class Context implements ContextInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ReferenceRepository
     */
    protected $referenceRepository;

    /**
     * @param ContainerInterface $container
     * @param Client $client
     * @param ReferenceRepository $referenceRepository
     */
    public function __construct(ContainerInterface $container, Client $client, ReferenceRepository $referenceRepository)
    {
        $this->container = $container;
        $this->client = $client;
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshReferences()
    {
        $referenceRepository = $this->getReferenceRepository();

        foreach ($referenceRepository->getReferences() as $name => $entity) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->getDoctrine()->getManager();
            $contains = $entityManager->contains($entity);

            if ($contains) {
                $entityRepository = $this->getEntityRepository(get_class($entity));
                $entityManager->refresh($entity);
            } else {
                // @todo If entity manager doesn't contain the referenced entity it should be reloaded.
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getReferenceRepository()
    {
        return $this->referenceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getReference($name)
    {
        return $this->getReferenceRepository()->getReference($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setReference($name, $object)
    {
        $this->getReferenceRepository()->setReference($name, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function addReference($name, $object)
    {
        $this->getReferenceRepository()->addReference($name, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityRepository($entityName)
    {
        return $this->getDoctrine()->getRepository($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getClient()
    {
        return $this->client;
    }
}
