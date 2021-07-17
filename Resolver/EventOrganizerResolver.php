<?php

namespace Oro\Bundle\CalendarBundle\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\UserBundle\Entity\User;

class EventOrganizerResolver
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function updateOrganizerInfo(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->isSystemEvent()) {
            return;
        }

        $calendarEvent->calculateIsOrganizer();
        if (!$calendarEvent->getOrganizerEmail()
            || ($calendarEvent->isOrganizer() && $calendarEvent->getOrganizerUser() !== null)
        ) {
            return;
        }

        /** @var User $user */
        $user = $this->findUser($calendarEvent->getOrganizerEmail());
        $defaultDisplayName = $calendarEvent->getOrganizerEmail();

        if ($user !== null) {
            $calendarEvent->setOrganizerUser($user);
            $defaultDisplayName = $user->getFullName();
        }

        if (!$calendarEvent->getOrganizerDisplayName()) {
            $calendarEvent->setOrganizerDisplayName($defaultDisplayName);
        }
    }

    /**
     * @param string $organizerEmail
     * @return User|null
     */
    private function findUser(string $organizerEmail)
    {
        return $this->registry->getManagerForClass(User::class)->getRepository(User::class)->findOneBy([
            'email' => $organizerEmail
        ]);
    }
}
