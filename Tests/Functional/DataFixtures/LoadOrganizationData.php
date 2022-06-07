<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadOrganizationData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name'      => 'Foo Inc.',
            'enabled'   => true,
            'reference' => 'oro_calendar:organization:foo',
        ],
        [
            'name'      => 'Bar Inc.',
            'enabled'   => true,
            'reference' => 'oro_calendar:organization:bar',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // Add system organization to reference repository
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $this->setReference('oro_calendar:organization:system', $organization);

        // Persist other organizations
        foreach ($this->data as $data) {
            $entity = new Organization();

            $this->setEntityPropertyValues($entity, $data, ['reference']);

            $this->setReference($data['reference'], $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
