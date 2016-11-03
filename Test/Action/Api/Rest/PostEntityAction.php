<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @internal
 *
 * {@inheritdoc}
 *
 * Supports additional parameters in configuration.
 * - entityClass. Class of entity created in the POST request.
 * - entityReference. Name of reference to add for the new entity.
 *
 * Example of configuration in YML:
 * <code>
 *      @test_api_rest_request:
 *          entityClass: "Foo\BarBundle\Entity\BazEntity"
 *          entityReference: "bazEntity"
 * </code>
 */
class PostEntityAction extends PostAction
{
    const ALIAS = 'test_api_rest_post_entity';

    /**
     * @var string|null
     */
    protected $entityClass;

    /**
     * @var string|null
     */
    protected $entityReference;

    /**
     * @param ContextInterface $context
     */
    protected function handleResponse(ContextInterface $context)
    {
        $client = $context->getClient();

        $this->assertResponseHeadersExpected($client);
        $response = $this->getResponseContent($client);

        $entity = $this->getEntityFromResponse($context, $response);

        $this->evaluateExpectedResponseContent($entity);
        $this->assertResponseContentExpected($client, $response);

        $this->addEntityReference($context, $entity);
    }

    /**
     * @param ContextInterface $context
     * @param array $response
     * @return object
     */
    protected function getEntityFromResponse(ContextInterface $context, array $response)
    {
        $client = $context->getClient();

        $entityId = $this->getEntityIdFromResponse($context, $response);

        WebTestCase::assertNotEmpty(
            $this->entityClass,
            'Failed asserting entity class is specified.'
        );

        $entity = $context->getEntityRepository($this->entityClass)->find($entityId);

        WebTestCase::assertNotEmpty(
            $entity,
            sprintf(
                'Failed asserting %s %s created new entity of %s (id=%d).',
                $client->getRequest()->getMethod(),
                $client->getRequest()->getRequestUri(),
                $this->entityClass,
                $response['id']
            )
        );

        return $entity;
    }

    /**
     * @param ContextInterface $context
     * @param array $response
     */
    protected function getEntityIdFromResponse(ContextInterface $context, array $response)
    {
        $client = $context->getClient();

        WebTestCase::assertNotEmpty(
            $response['id'],
            sprintf(
                'Failed asserting %s %s has id in response.',
                $client->getRequest()->getMethod(),
                $client->getRequest()->getRequestUri()
            )
        );

        return $response['id'];
    }

    /**
     * This method passes through configured responseContent to replaces found property paths with respective value
     * from gived $entity.
     *
     * @param string $entity
     */
    protected function evaluateExpectedResponseContent($entity)
    {
        foreach ($this->responseContent as $field => &$value) {
            if ($value instanceof PropertyPath) {
                $value = PropertyAccess::createPropertyAccessor()->getValue($entity, $value);
            }
        }
    }

    /**
     * @param ContextInterface $context
     * @param object $entity
     */
    protected function addEntityReference(ContextInterface $context, $entity)
    {
        if ($this->entityReference) {
            $context->addReference($this->entityReference, $entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        if (isset($options['entityReference'])) {
            $this->entityReference = $options['entityReference'];
        }

        if (isset($options['entityClass'])) {
            $this->entityClass = $options['entityClass'];
        }

        return $this;
    }
}
