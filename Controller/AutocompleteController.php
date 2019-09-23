<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Oro\Bundle\CalendarBundle\Autocomplete\AttendeeSearchHandler;
use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Autocomplete search controller for calendar attendee.
 *
 * @Route("/calendarevents/autocomplete")
 */
class AutocompleteController extends AbstractController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws HttpException
     *
     * @Route("/attendees", name="oro_calendarevent_autocomplete_attendees")
     */
    public function autocompleteAttendeesAction(Request $request)
    {
        $autocompleteRequest = new AutocompleteRequest($request);
        $validator           = $this->get(ValidatorInterface::class);
        $isXmlHttpRequest    = $request->isXmlHttpRequest();
        $code                = 200;
        $result              = [
            'results' => [],
            'hasMore' => false,
            'errors'  => []
        ];

        if ($violations = $validator->validate($autocompleteRequest)) {
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                $result['errors'][] = $violation->getMessage();
            }
        }

        if (!empty($result['errors'])) {
            if ($isXmlHttpRequest) {
                return new JsonResponse($result, $code);
            }

            throw new HttpException($code, implode(', ', $result['errors']));
        }

        $searchHandler = $this->get(AttendeeSearchHandler::class);

        return new JsonResponse($searchHandler->search(
            $autocompleteRequest->getQuery(),
            $autocompleteRequest->getPage(),
            $autocompleteRequest->getPerPage(),
            $autocompleteRequest->isSearchById()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ValidatorInterface::class,
                AttendeeSearchHandler::class,
            ]
        );
    }
}
