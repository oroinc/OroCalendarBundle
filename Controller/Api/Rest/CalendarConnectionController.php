<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CalendarBundle\Manager\CalendarPropertyApiEntityManager;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for calendar connections.
 */
class CalendarConnectionController extends RestController
{
    /**
     * Get calendar connections.
     *
     * @param int $id User's calendar id
     *
     * @ApiDoc(
     *      description="Get calendar connections",
     *      resource=true
     * )
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    #[AclAncestor('oro_calendar_view')]
    public function cgetAction($id)
    {
        $items = $this->getManager()->getCalendarManager()
            ->getCalendars(
                $this->container->get('oro_security.token_accessor')->getOrganization()->getId(),
                $this->getUser()->getId(),
                $id
            );

        return new Response(json_encode($items), Response::HTTP_OK);
    }

    /**
     * Update calendar connection.
     *
     * @param int $id Calendar connection id
     *
     * @ApiDoc(
     *      description="Update calendar connection",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_view')]
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new calendar connection.
     *
     * @ApiDoc(
     *      description="Create new calendar connection",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_view')]
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Remove calendar connection.
     *
     * @param int $id Calendar connection id
     *
     * @ApiDoc(
     *      description="Remove calendar connection",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_view')]
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @return CalendarPropertyApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_calendar.calendar_property.manager.api');
    }

    /**
     * @return Form
     */
    #[\Override]
    public function getForm()
    {
        return $this->container->get('oro_calendar.calendar_property.form.api');
    }

    /**
     * @return ApiFormHandler
     */
    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_calendar.calendar_property.form.handler.api');
    }

    #[\Override]
    protected function fixFormData(array &$data, $entity)
    {
        parent::fixFormData($data, $entity);

        unset(
            $data['calendarName'],
            $data['removable'],
            $data['canAddEvent'],
            $data['canEditEvent'],
            $data['canDeleteEvent']
        );

        return true;
    }
}
