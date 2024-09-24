<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
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
     *
     * @return Response
     */
    #[AclAncestor('oro_calendar_view')]
    public function getDefaultAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Organization $organization */
        $organization = $this->container->get('oro_security.token_accessor')->getOrganization();

        $em = $this->container->get('doctrine')->getManager();
        /** @var CalendarRepository $repo */
        $repo = $em->getRepository(Calendar::class);

        $calendar = $repo->findDefaultCalendar($user->getId(), $organization->getId());

        $result = array(
            'calendar'      => $calendar->getId(),
            'owner'         => $calendar->getOwner()->getId(),
            'calendarName'  => $calendar->getName(),
        );

        if (!$result['calendarName']) {
            $result['calendarName'] = $this->container->get('oro_entity.entity_name_resolver')
                ->getName($calendar->getOwner());
        }

        return new Response(json_encode($result), Response::HTTP_OK);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
