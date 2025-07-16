<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;
use Oro\Bundle\CalendarBundle\Twig\RecurrenceExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecurrenceExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TranslatorInterface&MockObject $translator;
    private StrategyInterface&MockObject $strategy;
    private RecurrenceExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->strategy = $this->createMock(StrategyInterface::class);

        $container = self::getContainerBuilder()
            ->add(TranslatorInterface::class, $this->translator)
            ->add('oro_calendar.model.recurrence', new Recurrence($this->strategy))
            ->getContainer($this);

        $this->extension = new RecurrenceExtension($container);
    }

    public function testGetRecurrenceTextValue(): void
    {
        $this->strategy->expects($this->once())
            ->method('getTextValue')
            ->willReturn('test_pattern');

        $this->assertEquals(
            'test_pattern',
            self::callTwigFunction($this->extension, 'get_recurrence_text_value', [new Entity\Recurrence()])
        );
    }

    public function testGetRecurrenceTextValueWithNA(): void
    {
        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'get_recurrence_text_value', [null])
        );
    }
}
