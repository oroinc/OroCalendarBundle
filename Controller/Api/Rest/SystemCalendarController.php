<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for SystemCalendar entity.
 */
class SystemCalendarController extends RestController
{
    /**
     * Remove system calendar.
     *
     * @param int $id System calendar id
     *
     * @ApiDoc(
     *      description="Remove system calendar",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_calendar.system_calendar.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Not implemented');
        //return $this->get('oro_calendar.system_calendar.form.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('Not implemented');
        //return $this->get('oro_calendar.system_calendar.form.handler.api');
    }
}
