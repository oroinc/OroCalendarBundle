<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Makes sure indexes of attendees from request are equal to indexes of the same
 * attendees so that in the end we end up with correct data.
 */
class AttendeesSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT  => ['fixSubmittedData', 100],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * Makes sure indexes of attendees from request are equal to indexes of the same
     * attendees so that in the end we end up with correct data.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function fixSubmittedData(FormEvent $event)
    {
        /** @var Attendee[]|Collection $data */
        $data      = $event->getData();
        $attendees = $event->getForm()->getData();
        if (!$attendees || !$data) {
            return;
        }

        $attendeeKeysByEmail = [];
        foreach ($attendees as $key => $attendee) {
            $id = $attendee->getEmail() ?: $attendee->getDisplayName();
            if (!$id) {
                return;
            }

            $attendeeKeysByEmail[$id] = $key;
        }

        $nextNewKey = count($attendeeKeysByEmail);
        $fixedData = [];
        foreach ($data as $attendee) {
            if (empty($attendee['email']) && empty($attendee['displayName'])) {
                return;
            }

            $id = empty($attendee['email']) ? $attendee['displayName'] : $attendee['email'];

            $key = isset($attendeeKeysByEmail[$id])
                ? $attendeeKeysByEmail[$id]
                : $nextNewKey++;

            $fixedData[$key] = $attendee;
        }

        $event->setData($fixedData);
    }
}
