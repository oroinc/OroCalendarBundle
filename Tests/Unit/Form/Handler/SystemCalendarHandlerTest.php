<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SystemCalendarHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request */
    private $request;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $om;

    /** @var SystemCalendar */
    private $entity;

    /** @var SystemCalendarHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->request = new Request();
        $this->om = $this->createMock(ObjectManager::class);
        $this->entity = new SystemCalendar();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->handler = new SystemCalendarHandler(
            $this->form,
            $requestStack,
            $this->om
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData(string $method)
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo(self::FORM_DATA));
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->om->expects($this->never())
            ->method('persist');
        $this->om->expects($this->never())
            ->method('flush');

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidData(string $method)
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo(self::FORM_DATA));
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->om->expects($this->once())
            ->method('persist');
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
