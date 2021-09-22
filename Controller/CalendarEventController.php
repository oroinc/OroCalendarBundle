<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Oro\Bundle\CalendarBundle\Controller\Api\Rest\CalendarEventController as RestCalendarEventController;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calendar event controller
 *
 * @Route("/event")
 */
class CalendarEventController extends AbstractController
{
    /**
     * @Route(name="oro_calendar_event_index")
     * @Template
     * @Acl(
     *      id="oro_calendar_event_view",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="VIEW",
     *      group_name=""
     * )
     */
    public function indexAction()
    {
        return [
            'entity_class' => CalendarEvent::class,
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_calendar_event_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_calendar_event_view")
     *
     * @param CalendarEvent $entity
     * @return array|RedirectResponse
     */
    public function viewAction(CalendarEvent $entity)
    {
        $this->checkPermissionByParentCalendar($entity, 'view');

        $loggedUser = $this->get(TokenAccessorInterface::class)->getUser();
        $canChangeInvitationStatus = $this->get(CalendarEventManager::class)
            ->canChangeInvitationStatus(
                $entity,
                $loggedUser
            );
        return [
            'entity' => $entity,
            'canChangeInvitationStatus' => $canChangeInvitationStatus
        ];
    }

    /**
     * @Route(
     *      "/widget/info/{id}/{renderContexts}",
     *      name="oro_calendar_event_widget_info",
     *      requirements={"id"="\d+", "renderContexts"="\d+"},
     *      defaults={"renderContexts"=true}
     * )
     * @Template
     * @AclAncestor("oro_calendar_event_view")
     *
     * @param Request $request
     * @param CalendarEvent $entity
     * @param integer|bool $renderContexts
     * @return array
     */
    public function infoAction(Request $request, CalendarEvent $entity, $renderContexts)
    {
        $this->checkPermissionByParentCalendar($entity, 'view');

        $loggedUser = $this->get(TokenAccessorInterface::class)->getUser();
        $canChangeInvitationStatus = $this->get(CalendarEventManager::class)
            ->canChangeInvitationStatus(
                $entity,
                $loggedUser
            );
        return [
            'entity'         => $entity,
            'target'         => $this->getTargetEntity($request),
            'renderContexts' => (bool) $renderContexts,
            'canChangeInvitationStatus' => $canChangeInvitationStatus
        ];
    }

    /**
     * This action is used to render the list of calendar events associated with the given entity
     * on the view page of this entity
     *
     * @Route("/activity/view/{entityClass}/{entityId}", name="oro_calendar_event_activity_view")
     * @AclAncestor("oro_calendar_event_view")
     * @Template
     *
     * @param string $entityClass
     * @param int $entityId
     * @return array
     */
    public function activityAction($entityClass, $entityId)
    {
        return [
            'entity' => $this->get(EntityRoutingHelper::class)->getEntity($entityClass, $entityId)
        ];
    }

    /**
     * @Route("/create", name="oro_calendar_event_create")
     * @Template("@OroCalendar/CalendarEvent/update.html.twig")
     * @Acl(
     *      id="oro_calendar_event_create",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="CREATE",
     *      group_name=""
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $entity = new CalendarEvent();

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime   = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime->add(new \DateInterval('PT1H'));
        $entity->setStart($startTime);
        $entity->setEnd($endTime);

        $formAction = $this->get(EntityRoutingHelper::class)
            ->generateUrlByRequest('oro_calendar_event_create', $request);

        return $this->update($request, $entity, $formAction);
    }

    /**
     * @Route("/update/{id}", name="oro_calendar_event_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_calendar_event_update",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="EDIT",
     *      group_name=""
     * )
     *
     * @param Request $request
     * @param CalendarEvent $entity
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, CalendarEvent $entity)
    {
        $this->checkPermissionByParentCalendar($entity, 'edit');

        $formAction = $this->get('router')->generate('oro_calendar_event_update', ['id' => $entity->getId()]);

        return $this->update($request, $entity, $formAction);
    }

    /**
     * Remove calendar event.
     *
     * @Route("/delete/{id}", name="oro_calendar_event_delete", requirements={"id"="\d+"})
     *
     * @Acl(
     *      id="oro_calendar_event_delete",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="DELETE",
     *      group_name=""
     * )
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function deleteAction(Request $request, $id)
    {
        return $this->forward(
            RestCalendarEventController::class . '::deleteAction',
            ['id' => $id, '_format' => 'json'],
            array_merge($request->query->all(), ['isCancelInsteadDelete' => true])
        );
    }

    /**
     * @param Request $request
     * @param CalendarEvent $entity
     * @param string        $formAction
     *
     * @return array
     */
    protected function update(Request $request, CalendarEvent $entity, $formAction)
    {
        $saved = false;

        $formHandler = $this->get(CalendarEventHandler::class);
        if ($formHandler->process($entity)) {
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
            'form'       => $formHandler->getForm()->createView(),
            'formAction' => $formAction
        ];
    }

    /**
     * Get target entity
     *
     * @param Request $request
     * @return object|null
     */
    protected function getTargetEntity(Request $request)
    {
        $entityRoutingHelper = $this->get(EntityRoutingHelper::class);
        $targetEntityClass   = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        $targetEntityId      = $entityRoutingHelper->getEntityId($request, 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }

        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    /**
     * Checks access to manipulate the calendar event by it's calendar
     * Temporary solution. Should be deleted in scope of BAP-13256
     *
     * @param CalendarEvent $entity
     * @param string        $action
     */
    protected function checkPermissionByParentCalendar(CalendarEvent $entity, $action)
    {
        $calendar = $entity->getCalendar();
        if (!$calendar) {
            throw $this->createNotFoundException('A system calendar event.');
        }

        if (!$this->isGranted('VIEW', $calendar)) {
            throw $this->createAccessDeniedException(
                sprintf('You does not have no access to %s this calendar event', $action)
            );
        }
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
                CalendarEventManager::class,
                CalendarEventHandler::class,
                EntityRoutingHelper::class,
                Router::class,
                TranslatorInterface::class,
            ]
        );
    }
}
