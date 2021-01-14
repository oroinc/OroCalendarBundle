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
    const FORM_DATA = ['field' => 'value'];

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $om;

    /** @var SystemCalendarHandler */
    protected $handler;

    /** @var SystemCalendar */
    protected $entity;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->om = $this->createMock(ObjectManager::class);

        $this->entity  = new SystemCalendar();
        $this->handler = new SystemCalendarHandler(
            $this->form,
            $requestStack,
            $this->om
        );
    }

    /**
     * @dataProvider supportedMethods
     *
     * @param string $method
     */
    public function testProcessInvalidData($method)
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
            ->will($this->returnValue(false));
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
     *
     * @param string $method
     */
    public function testProcessValidData($method)
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
            ->will($this->returnValue(true));
        $this->om->expects($this->once())
            ->method('persist');
        $this->om->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
