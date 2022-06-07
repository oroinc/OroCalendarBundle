<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Yaml\Yaml;

class LoadCalendarEventData extends AbstractFixture implements DependentFixtureInterface
{
    const CALENDAR_EVENT_TITLE = 'Regular event not in start:end range';
    const CALENDAR_EVENT_WITH_ATTENDEE = 'Event with attendee';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository(User::class)->findOneByUsername('admin');
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $calendar = $manager->getRepository(Calendar::class)->findDefaultCalendar(
            $user->getId(),
            $organization->getId()
        );
        $fileName = __DIR__ . DIRECTORY_SEPARATOR . 'calendar_event_fixture.yml';
        $records = Yaml::parse(file_get_contents($fileName));

        foreach ($records as $data) {
            $data['start'] = new \DateTime($data['start'], new \DateTimeZone('UTC'));
            $data['end'] = new \DateTime($data['end'], new \DateTimeZone('UTC'));
            $event = new CalendarEvent();
            $event->setCalendar($calendar);
            $this->setEntityPropertyValues($event, $data, ['reference', 'recurrence', 'exceptions']);

            if (!empty($data['recurrence'])) {
                $recurrence = new Recurrence();
                if (isset($data['recurrence']['type'])) {
                    $data['recurrence']['recurrenceType'] = $data['recurrence']['type'];
                    unset($data['recurrence']['type']);
                }
                $data['recurrence']['startTime'] = new \DateTime(
                    $data['recurrence']['startTime'],
                    new \DateTimeZone('UTC')
                );
                $this->setEntityPropertyValues($recurrence, $data['recurrence']);
                $event->setRecurrence($recurrence);
            }

            if (!empty($data['exceptions'])) {
                foreach ($data['exceptions'] as $exceptionData) {
                    $exception = new CalendarEvent();
                    $exception->setCalendar($calendar);
                    $exceptionData['start'] = new \DateTime($exceptionData['start'], new \DateTimeZone('UTC'));
                    $exceptionData['end'] = new \DateTime($exceptionData['end'], new \DateTimeZone('UTC'));
                    $exceptionData['originalStart'] = $data['start'];
                    if (isset($exceptionData['isCancelled'])) {
                        $exceptionData['cancelled'] = $exceptionData['isCancelled'];
                        unset($exceptionData['isCancelled']);
                    }
                    $this->setEntityPropertyValues($exception, $exceptionData);

                    $event->addRecurringEventException($exception);
                }
            }

            $manager->persist($event);

            if (!empty($data['reference'])) {
                $this->setReference($data['reference'], $event);
            }
        }

        $event    = new CalendarEvent();
        $attendee = new Attendee();
        $userName = 'user' . mt_rand(0, 100);
        $attendee->setEmail($userName.'@example.com');
        $attendee->setDisplayName($userName);
        $attendee->setUser($this->getReference('oro_calendar:user:system_user_1'));
        $event->setCalendar($calendar)
            ->setTitle(self::CALENDAR_EVENT_WITH_ATTENDEE)
            ->setStart(new \DateTime('+1 year', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('+1 year + 1 hour', new \DateTimeZone('UTC')))
            ->setAllDay(false);
        $manager->persist($event);

        $manager->flush();
    }
}
