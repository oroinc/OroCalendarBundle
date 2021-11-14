<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Type\SystemCalendarType;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemCalendarTypeTest extends TypeTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var SystemCalendarConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarConfig;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->calendarConfig = $this->createMock(SystemCalendarConfig::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_calendar.calendar_colors')
            ->willReturn(['#FF0000']);

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
    private function loadTypes(): array
    {
        $types = [
            SystemCalendarType::class => new SystemCalendarType($this->authorizationChecker, $this->calendarConfig),
            OroSimpleColorPickerType::class => new OroSimpleColorPickerType($this->configManager, $this->translator),
        ];

        $keys = array_map(
            function (AbstractType $type) {
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
            ->willReturn(true);
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap([
                ['oro_public_calendar_management', null, true],
                ['oro_system_calendar_management', null, true],
            ]);

        $form = $this->factory->create(SystemCalendarType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf(SystemCalendar::class, $result);
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
            ->willReturn(true);
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->willReturn(false);

        $form = $this->factory->create(SystemCalendarType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf(SystemCalendar::class, $result);
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
            ->willReturn(false);
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);

        $form = $this->factory->create(SystemCalendarType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf(SystemCalendar::class, $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertFalse($result->isPublic());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => SystemCalendar::class,
                    'csrf_token_id' => 'system_calendar',
                ]
            );

        $type = new SystemCalendarType($this->authorizationChecker, $this->calendarConfig);
        $type->configureOptions($resolver);
    }
}
