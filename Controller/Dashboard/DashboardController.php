<?php

namespace Oro\Bundle\CalendarBundle\Controller\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\CalendarBundle\Provider\CalendarDateTimeConfigProvider;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Calendar dashboard widget controller
 */
class DashboardController extends AbstractController
{
    /**
     * @Route(
     *      "/my_calendar/{widget}",
     *      name="oro_calendar_dashboard_my_calendar",
     *      requirements={"widget"="[\w\-]+"}
     * )
     * @Template("@OroCalendar/Dashboard/myCalendar.html.twig")
     * @param string $widget
     * @return array
     */
    public function myCalendarAction($widget)
    {
        $calendar = $this->get(ManagerRegistry::class)
            ->getRepository(Calendar::class)
            ->findDefaultCalendar(
                $this->getUser()->getId(),
                $this->get(TokenAccessorInterface::class)->getOrganization()->getId()
            );

        $currentDate = new \DateTime('now', new \DateTimeZone($this->get(LocaleSettings::class)->getTimeZone()));

        $startDate = clone $currentDate;
        $startDate->setTime(0, 0, 0);

        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P1D'));

        $firstHour = intval($currentDate->format('G'));
        if (intval($currentDate->format('i')) <= 30 && $firstHour !== 0) {
            $firstHour--;
        }

        $eventForm = $this->get(FormFactoryInterface::class)->createNamed(
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
                'timezoneOffset' => $this->get(CalendarDateTimeConfigProvider::class)->getTimezoneOffset()
            ],
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'firstHour'  => $firstHour
        ];

        return array_merge(
            $result,
            $this->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * {@inheritdoc}
     */
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
