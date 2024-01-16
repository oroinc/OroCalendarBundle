<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\Entity\User;

class LoadCalendarEventWithReminderData extends AbstractFixture implements DependentFixtureInterface
{
    public const EVENT_REFERENCE = 'oro_calendar:event:1';
    public const USER_REFERENCES = [
        'oro_calendar:user:foo_user_2',
        'oro_calendar:user:foo_user_3'
    ];
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    public function load(ObjectManager $manager)
    {
        /** @var User $owner */
        $owner = $this->getReference('oro_calendar:user:foo_user_1');
        $organization = $owner->getOrganizations()->first();
        $calendar = $manager->getRepository(Calendar::class)->findDefaultCalendar(
            $owner->getId(),
            $organization->getId()
        );

        $event = new CalendarEvent();
        $event
            ->setIsOrganizer(true)
            ->setCalendar($calendar)
            ->setTitle('Test event')
            ->setStart(new \DateTime('+1 year', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('+1 year + 1 hour', new \DateTimeZone('UTC')))
            ->setAllDay(false);

        $reminder = new Reminder();
        $reminder->setMethod('email')
            ->setInterval(new ReminderInterval(1, 'w'))
            ->setState(Reminder::STATE_NOT_SENT);

        $event->setReminders(new ArrayCollection([$reminder]));

        $users = array_map(function ($reference) {
            return $this->getReference($reference);
        }, self::USER_REFERENCES);

        /** @var User $user */
        foreach ($users as $user) {
            $attendee = new Attendee();
            $attendee->setEmail($user->getEmail())
                ->setDisplayName($user->getUsername())
                ->setUser($user);
            $event->addAttendee($attendee);
            $manager->persist($attendee);
        }
        $manager->persist($event);
        $manager->flush();

        $this->setReference(self::EVENT_REFERENCE, $event);
    }
}
