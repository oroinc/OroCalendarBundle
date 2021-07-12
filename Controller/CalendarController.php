<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\CalendarBundle\Provider\CalendarDateTimeConfigProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for viewing calendar information
 */
class CalendarController extends AbstractController
{
    /**
     * View user's default calendar
     *
     * @Route("/default", name="oro_calendar_view_default")
     * @Template
     * @AclAncestor("oro_calendar_view")
     */
    public function viewDefaultAction()
    {
        /** @var User $user */
        $user = $this->getUser();
        $organization = $this->get(TokenAccessorInterface::class)->getOrganization();

        /** @var CalendarRepository $repo */
        $repo = $this->get(ManagerRegistry::class)->getRepository(Calendar::class);

        $calendar = $repo->findDefaultCalendar($user->getId(), $organization->getId());

        return $this->viewAction($calendar);
    }

    /**
     * View calendar
     *
     * @Route("/view/{id}", name="oro_calendar_view", requirements={"id"="\d+"})
     *
     * @Template
     * @Acl(
     *      id="oro_calendar_view",
     *      type="entity",
     *      class="OroCalendarBundle:Calendar",
     *      permission="VIEW",
     *      group_name=""
     * )
     * @param Calendar $calendar
     * @return array
     */
    public function viewAction(Calendar $calendar)
    {
        $calendarConfigProvider = $this->get(CalendarDateTimeConfigProvider::class);
        $dateRange = $calendarConfigProvider->getDateRange();

        $formFactory = $this->get(FormFactoryInterface::class);
        $eventForm = $formFactory->createNamed(
            'oro_calendar_event_form',
            CalendarEventType::class,
            null,
            [
                'allow_change_calendar' => true,
                'layout_template' => true,
            ]
        );

        $userSelectForm = $formFactory->createNamed(
            'new_calendar',
            UserSelectType::class,
            null,
            [
                'autocomplete_alias' => 'user_calendars',

                'configs' => [
                    'entity_id'               => $calendar->getId(),
                    'entity_name'             => Calendar::class,
                    'excludeCurrent'          => true,
                    'component'               => 'acl-user-autocomplete',
                    'permission'              => 'VIEW',
                    'placeholder'             => 'oro.calendar.form.choose_user_to_add_calendar',
                    'result_template_twig'    => '@OroCalendar/Calendar/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroCalendar/Calendar/Autocomplete/selection.html.twig',
                ],

                'grid_name' => 'users-calendar-select-grid-exclude-owner',
                'random_id' => false,
                'required'  => true,
            ]
        );

        return [
            'event_form' => $eventForm->createView(),
            'user_select_form' => $userSelectForm->createView(),
            'entity' => $calendar,
            'calendar' => [
                'selectable' => $this->isGranted('oro_calendar_event_create'),
                'editable' => $this->isGranted('oro_calendar_event_update'),
                'removable' => $this->isGranted('oro_calendar_event_delete'),
                'timezoneOffset' => $calendarConfigProvider->getTimezoneOffset()
            ],
            'startDate' => $dateRange['startDate'],
            'endDate' => $dateRange['endDate'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TokenAccessorInterface::class,
                CalendarDateTimeConfigProvider::class,
                ManagerRegistry::class,
                FormFactoryInterface::class,
            ]
        );
    }
}
