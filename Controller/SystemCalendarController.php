<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

/**
 * System calendar controller.
 */
class SystemCalendarController extends Controller
{
    /**
     * @Route(name="oro_system_calendar_index")
     * @Template
     */
    public function indexAction()
    {
        $calendarConfig = $this->getCalendarConfig();
        if (!$calendarConfig->isPublicCalendarEnabled() && !$calendarConfig->isSystemCalendarEnabled()) {
            throw $this->createNotFoundException('Both Public and System calendars are disabled.');
        }

        if (!$this->isGranted('oro_public_calendar_management')
            && !$this->isGranted('oro_system_calendar_management')
        ) {
            throw new AccessDeniedException();
        }

        return [
            'entity_class' => $this->container->getParameter('oro_calendar.system_calendar.entity.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_system_calendar_view", requirements={"id"="\d+"})
     * @Template
     */
    public function viewAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);
        $this->checkSystemCalendarEntityAccess($entity);

        $isGranted = $entity->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return [
            'entity'      => $entity,
            'editable'    => $entity->isPublic()
                ? $this->isGranted('oro_public_calendar_management')
                : $this->isGranted('oro_system_calendar_management'),
            'removable'   => $entity->isPublic()
                ? $this->isGranted('oro_public_calendar_management')
                : $this->isGranted('oro_system_calendar_management'),
            'canAddEvent' => $entity->isPublic()
                ? $this->isGranted('oro_public_calendar_management')
                : $this->isGranted('oro_system_calendar_management'),
            'showScope'   =>
                $this->getCalendarConfig()->isPublicCalendarEnabled()
                && $this->getCalendarConfig()->isSystemCalendarEnabled()
        ];
    }

    /**
     * @Route("/create", name="oro_system_calendar_create")
     * @Template("OroCalendarBundle:SystemCalendar:update.html.twig")
     */
    public function createAction()
    {
        $calendarConfig = $this->getCalendarConfig();
        if (!$calendarConfig->isPublicCalendarEnabled() && !$calendarConfig->isSystemCalendarEnabled()) {
            throw $this->createNotFoundException('Both Public and System calendars are disabled.');
        }

        $isGranted = $this->isGranted('oro_public_calendar_management')
            || $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            new SystemCalendar(),
            $this->get('router')->generate('oro_system_calendar_create')
        );
    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_update", requirements={"id"="\d+"})
     * @Template("OroCalendarBundle:SystemCalendar:update.html.twig")
     */
    public function updateAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);
        $this->checkSystemCalendarEntityAccess($entity);

        $isGranted = $entity->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            $entity,
            $this->get('router')->generate('oro_system_calendar_update', ['id' => $entity->getId()])
        );
    }

    /**
     * @Route("/widget/events/{id}", name="oro_system_calendar_widget_events", requirements={"id"="\d+"})
     * @Template
     */
    public function eventsAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);
        $this->checkSystemCalendarEntityAccess($entity);

        if (!$entity->isPublic() && !$this->isGranted('oro_system_calendar_management')) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        return [
            'entity' => $entity
        ];
    }

    /**
     * @param SystemCalendar $entity
     * @param string         $formAction
     *
     * @return array
     */
    protected function update(SystemCalendar $entity, $formAction)
    {
        $saved = false;

        if ($this->get('oro_calendar.system_calendar.form.handler')->process($entity)) {
            if (!$this->getRequest()->get('_widgetContainer')) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.calendar.controller.systemcalendar.saved.message')
                );

                return $this->get('oro_ui.router')->redirect($entity);
            }
            $saved = true;
        }

        return array(
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $this->get('oro_calendar.system_calendar.form.handler')->getForm()->createView(),
            'formAction' => $formAction
        );
    }

    /**
     * @param SystemCalendar $entity
     *
     * @throws NotFoundHttpException
     */
    protected function checkPermissionByConfig(SystemCalendar $entity)
    {
        if ($entity->isPublic()) {
            if (!$this->getCalendarConfig()->isPublicCalendarEnabled()) {
                throw $this->createNotFoundException('Public calendars are disabled.');
            }
        } else {
            if (!$this->getCalendarConfig()->isSystemCalendarEnabled()) {
                throw $this->createNotFoundException('System calendars are disabled.');
            }
        }
    }

    /**
     * @return SystemCalendarConfig
     */
    protected function getCalendarConfig()
    {
        return $this->get('oro_calendar.system_calendar_config');
    }

    /**
     * @param SystemCalendar $entity
     */
    private function checkSystemCalendarEntityAccess(SystemCalendar $entity)
    {
        if (!$entity->isPublic()
            && $entity->getOrganization()->getId() !== $this->get('oro_security.token_accessor')->getOrganizationId()) {
            throw new AccessDeniedException();
        }
    }
}
