<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarPropertyApiType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarPropertyApiTypeTest extends TypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $em = $this->createMock(EntityManager::class);
        $meta = $this->createMock(ClassMetadata::class);
        $repo = $this->createMock(EntityRepository::class);
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 1);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($em);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($meta);
        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($repo);
        $meta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $repo->expects($this->any())
            ->method('find')
            ->with($calendar->getId())
            ->willReturn($calendar);

        return [
            new PreloadedExtension([
                new EntityIdentifierType($doctrine),
            ], [])
        ];
    }

    public function testSubmitValidData()
    {
        $formData = [
            'targetCalendar'  => 1,
            'calendarAlias'   => 'testCalendarAlias',
            'calendar'        => 2,
            'position'        => 100,
            'visible'         => true,
            'backgroundColor' => '#00FF00',
        ];

        $form = $this->factory->create(CalendarPropertyApiType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarProperty $result */
        $result = $form->getData();
        $this->assertInstanceOf(CalendarProperty::class, $result);
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 1);
        $this->assertEquals($calendar, $result->getTargetCalendar());
        $this->assertEquals('testCalendarAlias', $result->getCalendarAlias());
        $this->assertEquals(2, $result->getCalendar());
        $this->assertEquals(100, $result->getPosition());
        $this->assertTrue($result->getVisible());
        $this->assertEquals('#00FF00', $result->getBackgroundColor());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => CalendarProperty::class, 'csrf_protection' => false]);

        $type = new CalendarPropertyApiType();
        $type->configureOptions($resolver);
    }
}
