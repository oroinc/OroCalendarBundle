<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;

/**
 * Loads Attendee status and type enum options data.
 */
class LoadAttendeeData extends AbstractFixture implements DependentFixtureInterface
{
    protected array $statusEnumData = [
        Attendee::STATUS_NONE      => [
            'label'    => 'None',
            'priority' => 1,
            'default'  => true
        ],
        Attendee::STATUS_ACCEPTED  => [
            'label'    => 'Accepted',
            'priority' => 2,
            'default'  => false
        ],
        Attendee::STATUS_DECLINED  => [
            'label'    => 'Declined',
            'priority' => 3,
            'default'  => false
        ],
        Attendee::STATUS_TENTATIVE => [
            'label'    => 'Tentative',
            'priority' => 4,
            'default'  => false
        ]
    ];

    protected array $typeEnumData = [
        Attendee::TYPE_ORGANIZER => [
            'label'    => 'Organizer',
            'priority' => 1,
            'default'  => true
        ],
        Attendee::TYPE_OPTIONAL  => [
            'label'    => 'Optional',
            'priority' => 2,
            'default'  => false
        ],
        Attendee::TYPE_REQUIRED  => [
            'label'    => 'Required',
            'priority' => 3,
            'default'  => false
        ]
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadData($manager, Attendee::STATUS_ENUM_CODE, $this->statusEnumData);
        $this->loadData($manager, Attendee::TYPE_ENUM_CODE, $this->typeEnumData);
    }

    protected function loadData(ObjectManager $manager, string $enumCode, array $data): void
    {
        /** @var EnumOptionRepository $enumRepository */
        $enumRepository = $manager->getRepository(EnumOption::class);
        $existingValues = $enumRepository->findBy(['enumCode' => $enumCode]);
        $existingCodes  = [];

        /** @var EnumOptionInterface $existingValue */
        foreach ($existingValues as $existingValue) {
            $existingCodes[$existingValue->getInternalId()] = true;
        }
        foreach ($data as $key => $value) {
            if (!isset($existingCodes[$key])) {
                $enum = $enumRepository->createEnumOption(
                    $enumCode,
                    $key,
                    $value['label'],
                    $value['priority'],
                    $value['default']
                );

                $existingCodes[$key] = true;
                $manager->persist($enum);
            }
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }
}
