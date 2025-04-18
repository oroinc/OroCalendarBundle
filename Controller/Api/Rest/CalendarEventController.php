<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Exception\NotUserCalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * REST API CRUD controller for CalendarEvent entity.
 */
class CalendarEventController extends RestController
{
    // @codingStandardsIgnoreStart
    /**
     * Get calendar events. To get data, use either page/limit or start/end filters.
     *
     * @ApiDoc(
     *      description="Get calendar events",
     *      resource=true
     * )
     *
     * @param Request $request
     * @return Response
     */
    // @codingStandardsIgnoreEnd
    #[QueryParam(
        name: 'calendar',
        requirements: '\d+',
        description: 'Calendar id.',
        strict: true,
        nullable: false
    )]
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. defaults to 10.',
        nullable: true
    )]
    #[QueryParam(
        name: 'start',
        requirements: '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?',
        description: 'Start date in RFC 3339. For example: 2009-11-05T13:15:30Z.',
        strict: true,
        nullable: true
    )]
    #[QueryParam(
        name: 'end',
        requirements: '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?',
        description: 'End date in RFC 3339. For example: 2009-11-05T13:15:30Z.',
        strict: true,
        nullable: true
    )]
    #[QueryParam(
        name: 'subordinate',
        requirements: '(true)|(false)',
        default: false,
        description: 'Determines whether events from connected calendars should be included or not.',
        strict: true,
        nullable: true
    )]
    #[QueryParam(
        name: 'createdAt',
        requirements: '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?',
        description: 'Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00',
        nullable: true
    )]
    #[QueryParam(
        name: 'updatedAt',
        requirements: '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?',
        description: 'Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00',
        nullable: true
    )]
    #[QueryParam(
        name: 'recurringEventId',
        requirements: '\d+',
        description: "Filter events associated with recurring event. 
                      Recurring event will be returned as well. Does't work with start/end filters.",
        nullable: true
    )]
    #[QueryParam(
        name: 'uid',
        requirements: '.+',
        description: "iCalendar UID field (RFC5545). In most cases UUID format is used (RFC4122). 
                      Does't work with start/end filters.",
        nullable: true
    )]
    #[AclAncestor('oro_calendar_event_view')]
    public function cgetAction(Request $request)
    {
        $calendarId   = (int)$request->get('calendar');
        $subordinate  = (true == $request->get('subordinate'));
        $extendFields = $this->getExtendFieldNames(CalendarEvent::class);
        if ($request->get('start') && $request->get('end')) {
            $result = $this->container->get('oro_calendar.calendar_manager')->getCalendarEvents(
                $this->container->get('oro_security.token_accessor')->getOrganization()->getId(),
                $this->getUser()->getId(),
                $calendarId,
                new \DateTime($request->get('start')),
                new \DateTime($request->get('end')),
                $subordinate,
                $extendFields
            );
        } elseif ($request->get('page') && $request->get('limit')) {
            $dateParamFilter  = new HttpDateTimeParameterFilter();
            $filterParameters = ['createdAt' => $dateParamFilter, 'updatedAt' => $dateParamFilter];
            $parameters = ['createdAt', 'updatedAt', 'uid'];

            $recurringEventId = $request->get('recurringEventId');

            $filterCriteria   = $this->getFilterCriteria(
                $parameters,
                $filterParameters
            );

            /** @var CalendarEventRepository $repo */
            $repo  = $this->getManager()->getRepository();
            $qb    = $repo->getUserEventListByRecurringEventQueryBuilder(
                $filterCriteria,
                $extendFields,
                $recurringEventId
            );

            $page  = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);
            $qb
                ->andWhere('c.id = :calendarId')
                ->setParameter('calendarId', $calendarId);
            $qb->setMaxResults($limit)
                ->setFirstResult($page > 0 ? ($page - 1) * $limit : 0);

            $result = $this->container->get('oro_calendar.calendar_event_normalizer.user')->getCalendarEvents(
                $calendarId,
                $qb->getQuery()
            );

            return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result, 'query' => $qb]);
        } else {
            throw new BadRequestHttpException(
                'Time interval ("start" and "end") or paging ("page" and "limit") parameters should be specified.'
            );
        }

        return new Response(json_encode($result), Response::HTTP_OK);
    }

    /**
     * Get calendar event.
     *
     * @param int $id Calendar event id
     *
     * @ApiDoc(
     *      description="Get calendar event",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_event_view')]
    public function getAction(int $id)
    {
        /** @var CalendarEvent|null $entity */
        $entity = $this->getManager()->find($id);

        $result = null;
        $code   = Response::HTTP_NOT_FOUND;
        if ($entity) {
            $result = $this->container->get('oro_calendar.calendar_event_normalizer.user')
                ->getCalendarEvent(
                    $entity,
                    null,
                    $this->getExtendFieldNames(CalendarEvent::class)
                );
            $code   = Response::HTTP_OK;
        }

        return $this->buildResponse($result ?: '', self::ACTION_READ, ['result' => $result], $code);
    }

    /**
     * Get calendar event supposing it is displayed in the specified calendar.
     *
     * @param int $id      The id of a calendar where an event is displayed
     * @param int $eventId Calendar event id
     *
     * @ApiDoc(
     *      description="Get calendar event supposing it is displayed in the specified calendar",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_event_view')]
    public function getByCalendarAction($id, $eventId)
    {
        /** @var CalendarEvent|null $entity */
        $entity = $this->getManager()->find($eventId);

        $result = null;
        $code   = Response::HTTP_NOT_FOUND;
        if ($entity) {
            $result = $this->container->get('oro_calendar.calendar_event_normalizer.user')
                ->getCalendarEvent($entity, (int)$id);
            $code   = Response::HTTP_OK;
        }

        return $this->buildResponse($result ?: '', self::ACTION_READ, ['result' => $result], $code);
    }

    /**
     * Update calendar event.
     *
     * @param int $id Calendar event id
     *
     * @ApiDoc(
     *      description="Update calendar event",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_event_update')]
    public function putAction(int $id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new calendar event.
     *
     * @ApiDoc(
     *      description="Create new calendar event",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_event_create')]
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Remove calendar event.
     *
     * @param int $id Calendar event id
     *
     * @ApiDoc(
     *      description="Remove calendar event",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[Acl(
        id: 'oro_calendar_event_delete',
        type: 'entity',
        class: CalendarEvent::class,
        permission: 'DELETE',
        groupName: ''
    )]
    public function deleteAction(int $id)
    {
        $options = [];
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request) {
            if ((bool)$request->query->get('isCancelInsteadDelete', false)) {
                $options['cancelInsteadDelete'] = true;
            }
            $options['notifyAttendees'] = $request->query
                ->get('notifyAttendees', NotificationManager::NONE_NOTIFICATIONS_STRATEGY);
        }

        return $this->handleDeleteRequest($id, $options);
    }

    /**
     * @return ApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_calendar.calendar_event.manager.api');
    }

    /**
     * @return Form
     */
    #[\Override]
    public function getForm()
    {
        return $this->container->get('oro_calendar.calendar_event.form.api');
    }

    /**
     * @return ApiFormHandler
     */
    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_calendar.calendar_event.form.handler.api');
    }

    #[\Override]
    public function handleUpdateRequest($id)
    {
        /** @var CalendarEvent $entity */
        $entity = $this->getManager()->find($id);

        if ($entity) {
            try {
                $entity = $this->processForm($entity);
                if ($entity) {
                    $response = $this->createResponseData($entity);
                    unset($response['id']);
                    $view = $this->view($response, Response::HTTP_OK);
                } else {
                    $view = $this->view($this->getForm(), Response::HTTP_BAD_REQUEST);
                }
            } catch (AccessDeniedException $e) {
                $view = $this->view(['reason' => $e->getMessage()], Response::HTTP_FORBIDDEN);
            }
        } else {
            $view = $this->view(null, Response::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    #[\Override]
    public function handleCreateRequest($_ = null)
    {
        $isProcessed = false;

        $entity = call_user_func_array([$this, 'createEntity'], func_get_args());
        try {
            $entity = $this->processForm($entity);
            if ($entity) {
                $view        = $this->view($this->createResponseData($entity), Response::HTTP_CREATED);
                $isProcessed = true;
            } else {
                $view = $this->view($this->getForm(), Response::HTTP_BAD_REQUEST);
            }
        } catch (AccessDeniedException $e) {
            $view = $this->view(['reason' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (NotUserCalendarEvent $e) {
            $view = $this->view(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, self::ACTION_CREATE, ['success' => $isProcessed, 'entity' => $entity]);
    }

    #[\Override]
    protected function fixFormData(array &$data, $entity)
    {
        parent::fixFormData($data, $entity);

        // remove auxiliary attributes if any
        unset(
            $data['updatedAt'],
            $data['editable'],
            $data['editableInvitationStatus'],
            $data['removable']
        );

        return true;
    }

    /**
     * @param string $class
     *
     * @return array
     */
    protected function getExtendFieldNames($class)
    {
        $configProvider = $this->container->get('oro_entity_config.provider.extend');
        $configs        = $configProvider->filter(
            function (ConfigInterface $extendConfig) {
                return
                    $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM) &&
                    ExtendHelper::isFieldAccessible($extendConfig) &&
                    !$extendConfig->has('target_entity') &&
                    !$extendConfig->is('is_serialized');
            },
            $class
        );

        return array_map(
            function (ConfigInterface $config) {
                return $config->getId()->getFieldName();
            },
            $configs
        );
    }

    #[\Override]
    protected function createResponseData($entity)
    {
        $response        = parent::createResponseData($entity);
        $serializedEvent = $this->container->get('oro_calendar.calendar_event_normalizer.user')
            ->getCalendarEvent($entity);

        $response['uid']                      = (string)$serializedEvent['uid'];
        $response['organizerEmail']           = (string)$serializedEvent['organizerEmail'];
        $response['organizerDisplayName']     = (string)$serializedEvent['organizerDisplayName'];
        $response['organizerUserId']          = (string)$serializedEvent['organizerUserId'];
        $response['invitationStatus']         = (string)$serializedEvent['invitationStatus'];
        $response['editableInvitationStatus'] = $serializedEvent['editableInvitationStatus'];

        return $response;
    }
}
