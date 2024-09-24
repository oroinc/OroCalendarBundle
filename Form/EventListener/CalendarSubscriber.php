<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Makes sure that a calendar is set for a new calendar event.
 */
class CalendarSubscriber implements EventSubscriberInterface
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(TokenAccessorInterface $tokenAccessor, ManagerRegistry $registry)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->registry = $registry;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'fillCalendar',
        ];
    }

    /**
     * PRE_SET_DATA event handler
     */
    public function fillCalendar(FormEvent $event)
    {
        /** @var CalendarEvent $data */
        $data = $event->getData();
        if ($data && !$data->getId() && !$data->getCalendar() && !$data->getSystemCalendar()) {
            /** @var Calendar $defaultCalendar */
            $defaultCalendar = $this->registry
                ->getRepository(Calendar::class)
                ->findDefaultCalendar(
                    $this->tokenAccessor->getUserId(),
                    $this->tokenAccessor->getOrganizationId()
                );
            $data->setCalendar($defaultCalendar);
            $event->setData($data);
        }
    }
}
