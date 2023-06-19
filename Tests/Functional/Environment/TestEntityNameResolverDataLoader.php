<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;
    private UserManager $userManager;
    private TranslatorInterface $translator;

    public function __construct(
        TestEntityNameResolverDataLoaderInterface $innerDataLoader,
        UserManager $userManager,
        TranslatorInterface $translator
    ) {
        $this->innerDataLoader = $innerDataLoader;
        $this->userManager = $userManager;
        $this->translator = $translator;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Calendar::class === $entityClass) {
            $user = new User();
            $user->setOrganization($repository->getReference('organization'));
            $user->setOwner($repository->getReference('business_unit'));
            $user->setUsername('calendaruser');
            $user->setEmail('calendaruser@example.com');
            $user->setPassword($this->userManager->generatePassword());
            $user->setFirstName('John');
            $user->setMiddleName('M');
            $user->setLastName('Doo');
            $this->userManager->updateUser($user, false);

            $calendar = new Calendar();
            $calendar->setOrganization($repository->getReference('organization'));
            $calendar->setOwner($user);
            $calendar->setName('Test Calendar');
            $repository->setReference('calendar', $calendar);
            $em->persist($calendar);

            $calendarWithoutName = new Calendar();
            $calendarWithoutName->setOrganization($repository->getReference('organization'));
            $calendarWithoutName->setOwner($user);
            $repository->setReference('calendarWithoutName', $calendarWithoutName);
            $em->persist($calendarWithoutName);

            $systemCalendar = new Calendar();
            $systemCalendar->setName('Test System Calendar');
            $repository->setReference('systemCalendar', $systemCalendar);
            $em->persist($systemCalendar);

            $systemCalendarWithoutName = new Calendar();
            $repository->setReference('systemCalendarWithoutName', $systemCalendarWithoutName);
            $em->persist($systemCalendarWithoutName);

            $em->flush();

            return ['calendar', 'calendarWithoutName', 'systemCalendar', 'systemCalendarWithoutName'];
        }

        if (CalendarEvent::class === $entityClass) {
            $calendar = new Calendar();
            $calendar->setOrganization($repository->getReference('organization'));
            $calendar->setOwner($repository->getReference('user'));
            $calendar->setName('Test Calendar');
            $em->persist($calendar);
            $calendarEvent = new CalendarEvent();
            $calendarEvent->setCalendar($calendar);
            $calendarEvent->setStart(new \DateTime('+1 day', new \DateTimeZone('UTC')));
            $calendarEvent->setEnd(new \DateTime('+2 day', new \DateTimeZone('UTC')));
            $calendarEvent->setTitle('Test Calendar Event');
            $repository->setReference('calendarEvent', $calendarEvent);
            $em->persist($calendarEvent);
            $em->flush();

            return ['calendarEvent'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Calendar::class === $entityClass) {
            if ('calendarWithoutName' === $entityReference) {
                return 'John Doo';
            }
            if ('systemCalendar' === $entityReference) {
                return 'Test System Calendar';
            }
            if ('systemCalendarWithoutName' === $entityReference) {
                return $this->translator->trans(
                    'oro.calendar.label_not_available',
                    [],
                    null,
                    $locale && str_starts_with($locale, 'Localization ')
                        ? substr($locale, \strlen('Localization '))
                        : $locale
                );
            }

            return 'Test Calendar';
        }
        if (CalendarEvent::class === $entityClass) {
            return 'Test Calendar Event';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
