<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThan;
use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThanValidator;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DateEarlierThanValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DateEarlierThan
     */
    protected $constraint;

    /**
     * @var ExecutionContext
     */
    protected $context;

    /**
    * @var \DateTime
    */
    protected $dateTimeStart;

    /**
     * @var \DateTime
     */
    protected $dateTimeEnd;

    /**
     * @var \Symfony\Component\Form\Form
     */
    protected $formField;

    /**
     * @var DateEarlierThanValidator
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->dateTimeStart = new \DateTime('-1 day');
        $this->dateTimeEnd   = new \DateTime('+1 day');
        $this->constraint    = new DateEarlierThan('end');
        $this->validator     = new DateEarlierThanValidator();

        $this->formField = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->formField));

        $form->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->context->expects($this->any())
            ->method('getRoot')
            ->will($this->returnValue($form));

        $this->validator->initialize($this->context);
    }

    public function testValidateWhenNotSetArgumentType()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate(false, $this->constraint);
    }

    public function testValidateExceptionWhenInvalidArgumentType()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "DateTime", "string" given');

        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue('string'));
        $this->validator->validate('string', $this->constraint);
    }

    public function testValidateExceptionWhenInvalidConstraintType()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "DateTime", "string" given');

        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue('string'));
        $this->validator->validate($this->dateTimeStart, $this->constraint);
    }

    public function testValidateExceptionWhenRootTypeIsNotForm()
    {
        $data = new \stdClass();
        $data->start = new \DateTime();
        $data->end = new \DateTime();

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->context->expects($this->any())
            ->method('getRoot')
            ->will($this->returnValue($data));

        $this->context->expects($this->never())
            ->method('addViolation');

        $validator = new DateEarlierThanValidator();
        $validator->initialize($this->context);

        $validator->validate($this->dateTimeStart, $this->constraint);
    }

    public function testValidData()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->dateTimeEnd));

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($this->dateTimeStart, $this->constraint);
    }

    public function testInvalidData()
    {
        $this->formField->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->dateTimeStart));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                $this->equalTo($this->constraint->message),
                $this->equalTo(array('{{ ' . $this->constraint->getDefaultOption() . ' }}' => $this->constraint->field))
            );

        $this->validator->validate($this->dateTimeEnd, $this->constraint);
    }

    public function testNotExistingFormData()
    {
        $formConfig = $this->createMock('\Symfony\Component\Form\FormConfigInterface');
        $form = new Form($formConfig);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->context->expects($this->any())
            ->method('getRoot')
            ->will($this->returnValue($form));

        $this->validator->initialize($this->context);

        $this->assertNull($this->validator->validate(false, $this->constraint));
    }
}
