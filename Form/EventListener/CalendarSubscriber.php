<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarSubscriber implements EventSubscriberInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param SecurityFacade $securityFacade
     * @param ManagerRegistry $registry
     */
    public function __construct(SecurityFacade $securityFacade, ManagerRegistry $registry)
    {
        $this->securityFacade = $securityFacade;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'fillCalendar',
        ];
    }

    /**
     * PRE_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function fillCalendar(FormEvent $event)
    {
        /** @var CalendarEvent $data */
        $data = $event->getData();
        if ($data && !$data->getId() && !$data->getCalendar()) {
            /** @var Calendar $defaultCalendar */
            $defaultCalendar = $this->registry
                ->getRepository('OroCalendarBundle:Calendar')
                ->findDefaultCalendar(
                    $this->securityFacade->getLoggedUserId(),
                    $this->securityFacade->getOrganizationId()
                );
            $data->setCalendar($defaultCalendar);
            $event->setData($data);
        }
    }
}
