<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThan;
use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThanValidator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DateEarlierThanValidatorTest extends ConstraintValidatorTestCase
{
    /** @var \DateTime */
    private $dateTimeStart;

    /** @var \DateTime */
    private $dateTimeEnd;

    /** @var Form */
    private $formField;

    protected function setUp(): void
    {
        $this->dateTimeStart = new \DateTime('-1 day');
        $this->dateTimeEnd = new \DateTime('+1 day');

        $this->formField = $this->createMock(Form::class);

        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('get')
            ->willReturn($this->formField);
        $form->expects($this->any())
            ->method('has')
            ->willReturn(true);

        parent::setUp();
        $this->setRoot($form);
    }

    protected function createValidator()
    {
        return new DateEarlierThanValidator();
    }

    public function testValidateWhenNotSetArgumentType()
    {
        $constraint = new DateEarlierThan('end');
        $this->validator->validate(false, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateExceptionWhenInvalidArgumentType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "DateTime", "string" given');

        $this->formField->expects($this->any())
            ->method('getData')
            ->willReturn('string');

        $constraint = new DateEarlierThan('end');
        $this->validator->validate('string', $constraint);
    }

    public function testValidateExceptionWhenInvalidConstraintType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "DateTime", "string" given');

        $this->formField->expects($this->any())
            ->method('getData')
            ->willReturn('string');

        $constraint = new DateEarlierThan('end');
        $this->validator->validate($this->dateTimeStart, $constraint);
    }

    public function testValidateExceptionWhenRootTypeIsNotForm()
    {
        $data = new \stdClass();
        $data->start = new \DateTime();
        $data->end = new \DateTime();

        $this->setRoot($data);

        $constraint = new DateEarlierThan('end');
        $this->validator->validate($this->dateTimeStart, $constraint);

        $this->assertNoViolation();
    }

    public function testValidData()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->willReturn($this->dateTimeEnd);

        $constraint = new DateEarlierThan('end');
        $this->validator->validate($this->dateTimeStart, $constraint);

        $this->assertNoViolation();
    }

    public function testInvalidData()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->willReturn($this->dateTimeStart);

        $constraint = new DateEarlierThan('end');
        $this->validator->validate($this->dateTimeEnd, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ ' . $constraint->getDefaultOption() . ' }}', $constraint->field)
            ->assertRaised();
    }

    public function testNotExistingFormData()
    {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $form = new Form($formConfig);

        $this->setRoot($form);

        $constraint = new DateEarlierThan('end');
        $this->validator->validate(false, $constraint);

        $this->assertNoViolation();
    }
}
