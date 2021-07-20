<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SystemCalendarHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ObjectManager */
    protected $manager;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $manager
    ) {
        $this->form = $form;
        $this->requestStack = $requestStack;
        $this->manager = $manager;
    }

    /**
     * Get form, that build into handler, via handler service
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Process form
     *
     * @param SystemCalendar $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(SystemCalendar $entity)
    {
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     */
    protected function onSuccess(SystemCalendar $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
