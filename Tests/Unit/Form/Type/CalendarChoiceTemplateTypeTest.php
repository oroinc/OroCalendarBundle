<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarChoiceTemplateType;
use PHPUnit\Framework\TestCase;

class CalendarChoiceTemplateTypeTest extends TestCase
{
    private CalendarChoiceTemplateType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new CalendarChoiceTemplateType();
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_calendar_choice_template', $this->type->getName());
    }
}
