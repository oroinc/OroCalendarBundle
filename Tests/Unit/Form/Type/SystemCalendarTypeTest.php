<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Type\SystemCalendarType;
use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarTypeTest extends TypeTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function getExtensions()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->calendarConfig =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator    = $this->createMock('Symfony\Contracts\Translation\TranslatorInterface');
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_calendar.calendar_colors')
            ->will($this->returnValue(['#FF0000']));

        return [
            new PreloadedExtension(
                $this->loadTypes(),
                []
            )
        ];
    }

    /**
     * @return AbstractType[]
     */
    protected function loadTypes()
    {
        $types = [
            SystemCalendarType::class => new SystemCalendarType($this->authorizationChecker, $this->calendarConfig),
            OroSimpleColorPickerType::class => new OroSimpleColorPickerType($this->configManager, $this->translator),
        ];

        $keys = array_map(
            function ($type) {
                /* @var AbstractType $type */
                return $type->getName();
            },
            $types
        );

        return array_combine($keys, $types);
    }

    public function testSubmitValidData()
    {
        $formData = [
            'name'            => 'test',
            'backgroundColor' => '#FF0000',
            'public'          => '1'
        ];

        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_public_calendar_management', null, true],
                        ['oro_system_calendar_management', null, true],
                    ]
                )
            );

        $form = $this->factory->create(SystemCalendarType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\SystemCalendar', $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertTrue($result->isPublic());
    }

    public function testSubmitValidDataPublicCalendarOnly()
    {
        $formData = [
            'name'            => 'test',
            'backgroundColor' => '#FF0000',
            'public'          => '1'
        ];

        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $form = $this->factory->create(SystemCalendarType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\SystemCalendar', $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertTrue($result->isPublic());
    }

    public function testSubmitValidDataSystemCalendarOnly()
    {
        $formData = [
            'name'            => 'test',
            'backgroundColor' => '#FF0000',
            'public'          => '0'
        ];

        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $form = $this->factory->create(SystemCalendarType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\SystemCalendar', $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertFalse($result->isPublic());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'data_class' => 'Oro\Bundle\CalendarBundle\Entity\SystemCalendar',
                    'csrf_token_id' => 'system_calendar',
                )
            );

        $type = new SystemCalendarType($this->authorizationChecker, $this->calendarConfig);
        $type->configureOptions($resolver);
    }
}
