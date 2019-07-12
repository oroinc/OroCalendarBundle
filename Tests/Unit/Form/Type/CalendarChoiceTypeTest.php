<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarChoiceType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

class CalendarChoiceTypeTest extends TypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarEventManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function getExtensions()
    {
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');

        return [
            new PreloadedExtension([
                new CalendarChoiceType($this->calendarEventManager, $this->translator)
            ], [])
        ];
    }

    public function testSubmitValidData()
    {
        $entity   = new CalendarEvent();
        $formData = [
            'calendarUid' => 'system_123',
        ];

        $this->calendarEventManager->expects($this->any())
            ->method('getCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($alias, $id) {
                        return sprintf('%s_%d', $alias, $id);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('getSystemCalendars')
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'System1', 'public' => false]
                    ]
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('getUserCalendars')
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'User1']
                    ]
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('parseCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($uid) {
                        return explode('_', $uid);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($this->identicalTo($entity), 'system', 123)
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'User1']
                    ]
                )
            );

        $form = $this->factory->createNamed(
            'calendarUid',
            CalendarChoiceType::class,
            null,
            [
                'mapped'          => false,
                'auto_initialize' => false,
            ]
        );

        $parentForm = $this->factory->create(FormType::class, $entity);
        $parentForm->add($form);

        $parentForm->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitValidDataForExpanded()
    {
        $entity   = new CalendarEvent();
        $formData = [
            'calendarUid' => ['system_123'],
        ];

        $this->calendarEventManager->expects($this->any())
            ->method('getCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($alias, $id) {
                        return sprintf('%s_%d', $alias, $id);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('getSystemCalendars')
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'System1', 'public' => false]
                    ]
                )
            );
        $this->calendarEventManager->expects($this->never())
            ->method('getUserCalendars');
        $this->calendarEventManager->expects($this->once())
            ->method('parseCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($uid) {
                        return explode('_', $uid);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($this->identicalTo($entity), 'system', 123)
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'User1']
                    ]
                )
            );

        $form = $this->factory->createNamed(
            'calendarUid',
            CalendarChoiceType::class,
            null,
            [
                'mapped'          => false,
                'auto_initialize' => false,
                'is_new'          => true,
            ]
        );

        $parentForm = $this->factory->create(FormType::class, $entity);
        $parentForm->add($form);

        $parentForm->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testGetParent()
    {
        $type = new CalendarChoiceType($this->calendarEventManager, $this->translator);
        $this->assertEquals(ChoiceType::class, $type->getParent());
    }
}
