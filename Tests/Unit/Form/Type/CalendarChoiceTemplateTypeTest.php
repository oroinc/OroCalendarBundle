<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarChoiceTemplateType;

class CalendarChoiceTemplateTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarChoiceTemplateType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new CalendarChoiceTemplateType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_choice_template', $this->type->getName());
    }
}
