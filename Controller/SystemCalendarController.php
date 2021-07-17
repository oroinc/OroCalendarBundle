<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * System calendar controller.
 */
class SystemCalendarController extends AbstractController
{
    /**
     * @Route(name="oro_system_calendar_index")
     * @Template
     */
    public function indexAction()
    {
        $this->checkPublicAndSystemCalendarsEnabled();

        return [
            'entity_class' => SystemCalendar::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_system_calendar_view", requirements={"id"="\d+"})
     * @Template
     */
    public function viewAction(SystemCalendar $entity)
    {
        $this->checkCalendarPermissions($entity);

        $calendarConfig = $this->getCalendarConfig();

        return [
            'entity'      => $entity,
            'editable'    => true,
            'removable'   => true,
            'canAddEvent' => true,
            'showScope'   => $calendarConfig->isPublicCalendarEnabled() && $calendarConfig->isSystemCalendarEnabled()
        ];
    }

    /**
     * @Route("/create", name="oro_system_calendar_create")
     * @Template("OroCalendarBundle:SystemCalendar:update.html.twig")
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $this->checkPublicAndSystemCalendarsEnabled();

        return $this->update(
            $request,
            new SystemCalendar(),
            $this->get('router')->generate('oro_system_calendar_create')
        );
    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_update", requirements={"id"="\d+"})
     * @Template("OroCalendarBundle:SystemCalendar:update.html.twig")
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, SystemCalendar $entity)
    {
        $this->checkCalendarPermissions($entity);

        return $this->update(
            $request,
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
        $this->checkCalendarPermissions($entity);

        return [
            'entity' => $entity
        ];
    }

    /**
     * @param Request $request
     * @param SystemCalendar $entity
     * @param string $formAction
     *
     * @return array
     */
    protected function update(Request $request, SystemCalendar $entity, $formAction)
    {
        $saved = false;

        if ($this->get('oro_calendar.system_calendar.form.handler')->process($entity)) {
            if (!$request->get('_widgetContainer')) {
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
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    private function checkPublicAndSystemCalendarsEnabled()
    {
        $calendarConfig = $this->getCalendarConfig();
        if (!$calendarConfig->isPublicCalendarEnabled() && !$calendarConfig->isSystemCalendarEnabled()) {
            throw $this->createNotFoundException('Both Public and System calendars are disabled.');
        }

        if (!$this->isPublicCalendarManagementEnabled() && !$this->isSystemCalendarManagementEnabled()) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    private function checkCalendarPermissions(SystemCalendar $entity)
    {
        $calendarConfig = $this->getCalendarConfig();
        if ($entity->isPublic()) {
            if (!$calendarConfig->isPublicCalendarEnabled()) {
                throw $this->createNotFoundException('Public calendars are disabled.');
            }
        } elseif (!$calendarConfig->isSystemCalendarEnabled()) {
            throw $this->createNotFoundException('System calendars are disabled.');
        }

        $isGranted = $entity->isPublic()
            ? $this->isPublicCalendarManagementEnabled()
            : $this->isSystemCalendarManagementEnabled();
        if (!$isGranted) {
            throw new AccessDeniedException();
        }
        if (!$entity->isPublic()
            && $entity->getOrganization()->getId() !== $this->getSecurityTokenAccessor()->getOrganizationId()
        ) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @return SystemCalendarConfig
     */
    private function getCalendarConfig()
    {
        return $this->get('oro_calendar.system_calendar_config');
    }

    /**
     * @return TokenAccessorInterface
     */
    private function getSecurityTokenAccessor()
    {
        return $this->get('oro_security.token_accessor');
    }

    /**
     * @return bool
     */
    private function isPublicCalendarManagementEnabled()
    {
        return $this->isGranted('oro_public_calendar_management');
    }

    /**
     * @return bool
     */
    private function isSystemCalendarManagementEnabled()
    {
        return $this->isGranted('oro_system_calendar_management');
    }
}
