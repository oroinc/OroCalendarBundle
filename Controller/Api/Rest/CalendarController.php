<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get default calendar.
 */
class CalendarController extends AbstractFOSRestController
{
    /**
     * Get Default Calendar of User
     *
     * @ApiDoc(
     *      description="Get default calendar of user",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_view")
     *
     * @return Response
     */
    public function getDefaultAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Organization $organization */
        $organization = $this->get('oro_security.token_accessor')->getOrganization();

        $em = $this->getDoctrine()->getManager();
        /** @var CalendarRepository $repo */
        $repo = $em->getRepository('OroCalendarBundle:Calendar');

        $calendar = $repo->findDefaultCalendar($user->getId(), $organization->getId());

        $result = array(
            'calendar'      => $calendar->getId(),
            'owner'         => $calendar->getOwner()->getId(),
            'calendarName'  => $calendar->getName(),
        );

        if (!$result['calendarName']) {
            $result['calendarName'] = $this->get('oro_entity.entity_name_resolver')->getName($calendar->getOwner());
        }

        return new Response(json_encode($result), Response::HTTP_OK);
    }
}
