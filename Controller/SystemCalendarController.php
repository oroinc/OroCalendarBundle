<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
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
     * @Template("@OroCalendar/SystemCalendar/update.html.twig")
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
     * @Template("@OroCalendar/SystemCalendar/update.html.twig")
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

        $systemCalendarFormHandler = $this->get(SystemCalendarHandler::class);
        if ($systemCalendarFormHandler->process($entity)) {
            if (!$request->get('_widgetContainer')) {
                $translator = $this->get(TranslatorInterface::class);
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $translator->trans('oro.calendar.controller.systemcalendar.saved.message')
                );

                return $this->get(Router::class)->redirect($entity);
            }
            $saved = true;
        }

        return [
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $systemCalendarFormHandler->getForm()->createView(),
            'formAction' => $formAction
        ];
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

    private function getCalendarConfig(): SystemCalendarConfig
    {
        return $this->get(SystemCalendarConfig::class);
    }

    private function getSecurityTokenAccessor(): TokenAccessorInterface
    {
        return $this->get(TokenAccessorInterface::class);
    }

    private function isPublicCalendarManagementEnabled(): bool
    {
        return $this->isGranted('oro_public_calendar_management');
    }

    private function isSystemCalendarManagementEnabled(): bool
    {
        return $this->isGranted('oro_system_calendar_management');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                SystemCalendarHandler::class,
                TranslatorInterface::class,
                Router::class,
                TokenAccessorInterface::class,
                SystemCalendarConfig::class,
            ]
        );
    }
}
