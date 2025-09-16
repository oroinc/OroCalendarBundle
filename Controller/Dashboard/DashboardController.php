<?php

namespace Oro\Bundle\CalendarBundle\Controller\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\CalendarBundle\Provider\CalendarDateTimeConfigProvider;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Calendar dashboard widget controller
 */
class DashboardController extends AbstractController
{
    /**
     * @param string $widget
     * @return array
     */
    #[Route(
        path: '/my_calendar/{widget}',
        name: 'oro_calendar_dashboard_my_calendar',
        requirements: ['widget' => '[\w\-]+']
    )]
    #[Template('@OroCalendar/Dashboard/myCalendar.html.twig')]
    public function myCalendarAction($widget)
    {
        $calendar = $this->container->get(ManagerRegistry::class)
            ->getRepository(Calendar::class)
            ->findDefaultCalendar(
                $this->getUser()->getId(),
                $this->container->get(TokenAccessorInterface::class)->getOrganization()->getId()
            );

        $currentDate = new \DateTime(
            'now',
            new \DateTimeZone($this->container->get(LocaleSettings::class)->getTimeZone())
        );

        $startDate = clone $currentDate;
        $startDate->setTime(0, 0, 0);

        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P1D'));

        $firstHour = intval($currentDate->format('G'));
        if (intval($currentDate->format('i')) <= 30 && $firstHour !== 0) {
            $firstHour--;
        }

        $eventForm = $this->container->get(FormFactoryInterface::class)->createNamed(
            'oro_calendar_event_form',
            CalendarEventType::class,
            null,
            [
                'allow_change_calendar' => true,
                'layout_template' => true,
            ]
        );

        $result = [
            'event_form' => $eventForm->createView(),
            'entity'     => $calendar,
            'calendar'   => [
                'selectable'     => $this->isGranted('oro_calendar_event_create'),
                'editable'       => $this->isGranted('oro_calendar_event_update'),
                'removable'      => $this->isGranted('oro_calendar_event_delete'),
                'timezoneOffset' => $this->container->get(CalendarDateTimeConfigProvider::class)->getTimezoneOffset()
            ],
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'firstHour'  => $firstHour
        ];

        return array_merge(
            $result,
            $this->container->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TokenAccessorInterface::class,
            CalendarDateTimeConfigProvider::class,
            LocaleSettings::class,
            WidgetConfigs::class,
            ManagerRegistry::class,
            FormFactoryInterface::class,
        ]);
    }
}
