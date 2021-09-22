<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarEventHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Back-office CRUD for system calendar events.
 */
class SystemCalendarEventController extends AbstractController
{
    /**
     * @Route("/event/view/{id}", name="oro_system_calendar_event_view", requirements={"id"="\d+"})
     * @Template
     *
     * @param CalendarEvent $entity
     * @return array
     */
    public function viewAction(CalendarEvent $entity)
    {
        $calendar = $entity->getSystemCalendar();

        $this->checkPermissions($calendar);

        $isEventManagementGranted = $calendar->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');

        return [
            'entity'    => $entity,
            'editable'  => $isEventManagementGranted,
            'removable' => $isEventManagementGranted
        ];
    }

    /**
     * @Route(
     *      "/widget/info/{id}/{renderContexts}",
     *      name="oro_system_calendar_event_widget_info",
     *      requirements={"id"="\d+", "renderContexts"="\d+"},
     *      defaults={"renderContexts"=true}
     * )
     * @Template
     *
     * @param Request $request
     * @param CalendarEvent $entity
     * @param int $renderContexts
     * @return array
     */
    public function infoAction(Request $request, CalendarEvent $entity, $renderContexts)
    {
        $calendar = $entity->getSystemCalendar();

        $this->checkPermissions($calendar);

        return [
            'entity' => $entity,
            'target' => $this->getTargetEntity($request),
            'renderContexts' => (bool)$renderContexts
        ];
    }

    /**
     * @Route("/{id}/event/create", name="oro_system_calendar_event_create", requirements={"id"="\d+"})
     * @Template("@OroCalendar/SystemCalendarEvent/update.html.twig")
     * @param Request $request
     * @param SystemCalendar $calendar
     * @return array|RedirectResponse
     */
    public function createAction(Request $request, SystemCalendar $calendar)
    {
        $this->checkPermissionByConfig($calendar);

        $isGranted = $calendar->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        $entity = new CalendarEvent();

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime   = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime->add(new \DateInterval('PT1H'));
        $entity->setStart($startTime);
        $entity->setEnd($endTime);
        $entity->setSystemCalendar($calendar);

        return $this->update(
            $request,
            $entity,
            $this->get('router')->generate('oro_system_calendar_event_create', ['id' => $calendar->getId()])
        );
    }

    /**
     * @Route("/event/update/{id}", name="oro_system_calendar_event_update", requirements={"id"="\d+"})
     * @Template
     * @param Request $request
     * @param CalendarEvent $entity
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, CalendarEvent $entity)
    {
        $calendar = $entity->getSystemCalendar();
        if (!$calendar) {
            // an event must belong to system calendar
            throw $this->createNotFoundException('Not system calendar event.');
        }

        $this->checkPermissionByConfig($calendar);

        if (!$calendar->isPublic() && !$this->isGranted('VIEW', $calendar)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        $isGranted = $calendar->isPublic()
            ? $this->isGranted('oro_public_calendar_management')
            : $this->isGranted('oro_system_calendar_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            $request,
            $entity,
            $this->get('router')->generate('oro_system_calendar_event_update', ['id' => $entity->getId()])
        );
    }

    /**
     * @param Request $request
     * @param CalendarEvent $entity
     * @param string $formAction
     *
     * @return array
     */
    protected function update(Request $request, CalendarEvent $entity, $formAction)
    {
        $saved = false;

        if ($this->get(SystemCalendarEventHandler::class)->process($entity)) {
            if (!$request->get('_widgetContainer')) {
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->get(TranslatorInterface::class)->trans('oro.calendar.controller.event.saved.message')
                );

                return $this->get(Router::class)->redirect($entity);
            }
            $saved = true;
        }

        return [
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $this->get(CalendarEventHandler::class)->getForm()->createView(),
            'formAction' => $formAction
        ];
    }

    /**
     * @param Request $request
     * @return object|null
     */
    private function getTargetEntity(Request $request)
    {
        $entityRoutingHelper = $this->get(EntityRoutingHelper::class);
        $targetEntityClass = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        $targetEntityId = $entityRoutingHelper->getEntityId($request, 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }

        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    private function checkPermissions(SystemCalendar $systemCalendar = null)
    {
        if (!$systemCalendar) {
            // an event must belong to system calendar
            throw $this->createNotFoundException('Not system calendar event.');
        }

        $this->checkPermissionByConfig($systemCalendar);

        if (!$systemCalendar->isPublic() && !$this->isGranted('VIEW', $systemCalendar)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }
    }

    /**
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

    protected function getCalendarConfig(): SystemCalendarConfig
    {
        return $this->get(SystemCalendarConfig::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                EntityRoutingHelper::class,
                SystemCalendarEventHandler::class,
                CalendarEventHandler::class,
                SystemCalendarConfig::class,
            ]
        );
    }
}
