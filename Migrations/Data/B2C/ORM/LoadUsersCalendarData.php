<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\DemoDataCRMProBundle\Migrations\Data\B2C\ORM\AbstractFixture;
use Oro\Bundle\DemoDataCRMProBundle\Model\WeekendCheckerTrait;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads new Calendar entities.
 */
class LoadUsersCalendarData extends AbstractFixture implements OrderedFixtureInterface
{
    use WeekendCheckerTrait;

    /** @var CalendarRepository */
    protected $calendarRepository;

    /** @var  EntityRepository */
    protected $roleRepository;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->calendarRepository = $this->em->getRepository('OroCalendarBundle:Calendar');
        $this->roleRepository     = $this->em->getRepository('OroUserBundle:Role');
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'day',
                'start time',
                'end time'
            ]
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        $absolutePath = $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/B2C/ORM/' . static::DATA_FOLDER);

        return [
            'events'      => $this->loadDataFromCSV($absolutePath.'/calendar/events.csv'),
            'connections' => $this->loadDataFromCSV($absolutePath.'/calendar/connections.csv')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data      = $this->getData();
        $calendars = $this->calendarRepository->findAll();
        $events    = array_reduce(
            $data['events'],
            function ($carry, $item) {
                $carry[$item['day']][] = $item;
                return $carry;
            },
            []
        );

        /** @var Role $userRole */
        $userRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_USER]);

        /** @var Calendar $calendar */
        foreach ($calendars as $calendar) {
            /**
             * Skip user with ROLE_USER
             */
            if ($calendar->getOwner()->hasRole($userRole)) {
                continue;
            }

            $created = $calendar->getOwner()->getCreatedAt();
            $manager->getClassMetadata('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')->setLifecycleCallbacks([]);

            for ($i = 0; array_key_exists($i, $data['events']); $created->add(new \DateInterval('P1D'))) {
                $dayEvents = array_key_exists($i, $events) ? $events[$i] : [];
                if (!$this->isWeekEnd($created)) {
                    foreach ($dayEvents as $eventData) {
                        $event = new CalendarEvent();
                        $this->setObjectValues($event, $eventData);
                        $start     = new \DateTime($created->format('Y-m-d') . ' ' . $eventData['start time']);
                        $end       = new \DateTime($created->format('Y-m-d') . ' ' . $eventData['end time']);
                        $createdAt = clone $start;
                        $event
                            ->setStart($start)
                            ->setEnd($end)
                            ->setCreatedAt($createdAt->modify('-1 day'));
                        $event->setUpdatedAt(
                            (new \DateTime())->setTimestamp(rand($start->getTimestamp(), $end->getTimestamp()))
                        );
                        $calendar->addEvent($event);
                        $this->setSecurityContext($calendar->getOwner());
                    }
                    $i++;
                }
            }
        }

        foreach ($data['connections'] as $connection) {
            $user             = $this->getUserReference($connection['user_uid']);
            $targetUser       = $this->getUserReference($connection['target_user_uid']);
            $calendar         = $this->calendarRepository->findDefaultCalendar(
                $user->getId(),
                $user->getOrganization()->getId()
            );
            $targetCalendar   = $this->calendarRepository->findDefaultCalendar(
                $targetUser->getId(),
                $targetUser->getOrganization()->getId()
            );
            $calendarProperty = new CalendarProperty();
            $calendarProperty
                ->setTargetCalendar($targetCalendar)
                ->setCalendarAlias('user')
                ->setCalendar($calendar->getId())
                ->setPosition($connection['position'])
                ->setBackgroundColor($connection['background_color']);
            $this->em->persist($calendarProperty);
        }

        $manager->flush();
        $this->cleanSecurityContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 6;
    }
}
