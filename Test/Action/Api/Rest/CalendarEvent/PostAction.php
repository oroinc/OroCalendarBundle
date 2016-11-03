<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\PostEntityAction;
use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @internal
 *
 * {@inheritdoc}
 *
 * Supports additional parameters in configuration.
 * - referenceToChildEventOwnerMapping. Mapping of child event owners to reference names. Can be used to save references
 *                                      for child events.
 *
 * Example of configuration in YML:
 * <code>
 *      @test_api_rest_post_calendar_event:
 *          "child_event_reference": "reference('reference_to_child_event_calendar_owner')"
 * </code>
 */
class PostAction extends PostEntityAction
{
    const ALIAS = 'test_api_rest_post_calendar_event';

    /**
     * The array contains reference name as a key and id of child event owner user as a value.
     *
     * @var User[]
     */
    protected $referenceToChildEventOwnerMapping;

    /**
     * @param ContextInterface $context
     * @param CalendarEvent $entity
     */
    protected function addEntityReference(ContextInterface $context, $entity)
    {
        parent::addEntityReference($context, $entity);
        $this->addChildEventReferences($context, $entity);
    }

    /**
     * Add references for child events.
     *
     * @param ContextInterface $context
     * @param CalendarEvent $parentEvent
     */
    protected function addChildEventReferences(ContextInterface $context, CalendarEvent $parentEvent)
    {
        if (!$this->referenceToChildEventOwnerMapping) {
            return;
        }

        $childEvents = [];

        foreach ($parentEvent->getChildEvents() as $childEvent) {
            $childEvents[$childEvent->getCalendar()->getOwner()->getId()] = $childEvent;
        }

        foreach ($this->referenceToChildEventOwnerMapping as $newReferenceName => $user) {
            WebTestCase::assertInstanceOf(
                User::class,
                $user,
                sprintf(
                    'Failed asserting element of configuration ' .
                    'referenceToChildEventOwnerMapping[%s] is an instance of %s.',
                    $newReferenceName,
                    User::class
                )
            );

            $childEventCalendarOwnerId = $user->getId();
            WebTestCase::assertArrayHasKey(
                $childEventCalendarOwnerId,
                $childEvents,
                sprintf(
                    'Failed asserting calendar event "%s" (id=%d) has child event owned by user "%s" (id=%d).',
                    $parentEvent->getTitle(),
                    $parentEvent->getId(),
                    $user->getUsername(),
                    $user->getId()
                )
            );

            $context->addReference($newReferenceName, $childEvents[$childEventCalendarOwnerId]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        if (isset($options['referenceToChildEventOwnerMapping'])) {
            $this->referenceToChildEventOwnerMapping = $options['referenceToChildEventOwnerMapping'];
        }

        $this->requestRoute = 'oro_api_post_calendarevent';
        $this->entityClass = CalendarEvent::class;

        return $this;
    }
}
