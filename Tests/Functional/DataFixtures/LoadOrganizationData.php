<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadOrganizationData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        'oro_calendar:organization:foo' => [
            'name'      => 'Foo Inc.',
            'enabled'   => true
        ],
        'oro_calendar:organization:bar' => [
            'name'      => 'Bar Inc.',
            'enabled'   => true
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // Add system organization to reference repository
        $this->setReference('oro_calendar:organization:system', $this->getReference(LoadOrganization::ORGANIZATION));
        // Persist other organizations
        foreach ($this->data as $reference => $data) {
            $entity = new Organization();
            $entity->setName($data['name']);
            $entity->setEnabled($data['enabled']);
            $this->setReference($reference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
