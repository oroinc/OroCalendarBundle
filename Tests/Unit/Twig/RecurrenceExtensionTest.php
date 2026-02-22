<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Twig\RecurrenceExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecurrenceExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private Recurrence&MockObject $recurrenceModel;
    private RecurrenceExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->recurrenceModel = $this->createMock(Recurrence::class);

        $container = self::getContainerBuilder()
            ->add(Recurrence::class, $this->recurrenceModel)
            ->getContainer($this);

        $this->extension = new RecurrenceExtension($container);
    }

    public function testGetRecurrenceTextValue(): void
    {
        $this->recurrenceModel->expects($this->once())
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
