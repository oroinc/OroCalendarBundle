<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository(Calendar::class);
        $referenceRepository->set('first_calendar', $repository->find(1));
    }
}
