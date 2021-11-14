<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class AttendeesSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttendeesSubscriber */
    private $attendeesSubscriber;

    protected function setUp(): void
    {
        $this->attendeesSubscriber = new AttendeesSubscriber();
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SUBMIT  => ['fixSubmittedData', 100],
            ],
            $this->attendeesSubscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider preSubmitProvider
     */
    public function testPreSubmit(array $eventData, array|ArrayCollection $formData, array $expectedData)
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($formData);

        $event = new FormEvent($form, $eventData);
        $this->attendeesSubscriber->fixSubmittedData($event);
        $this->assertEquals($expectedData, $event->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function preSubmitProvider(): array
    {
        return [
            'empty attendees' => [
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
                [],
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
            ],
            'empty data' => [
                [],
                [
                    'displayName' => 'existing',
                    'email' => 'existing@example.com',
                ],
                [],
            ],
            'missing email and displayName in attendees' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee()),
                ]),
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
            'missing email in data' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee())
                        ->setDisplayName('toBeRemoved')
                        ->setEmail('toBeRemoved@example.com'),
                ]),
                [
                    0 => [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    2 => [
                        'displayName' => 'new',
                    ],
                    3 => [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
            'valid data' => [
                [
                    [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    [
                        'displayName' => 'new',
                        'email' => 'new@example.com',
                    ],
                    [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
                new ArrayCollection([
                    (new Attendee())
                        ->setDisplayName('existing')
                        ->setEmail('existing@example.com'),
                    (new Attendee())
                        ->setDisplayName('toBeRemoved')
                        ->setEmail('toBeRemoved@example.com'),
                ]),
                [
                    0 => [
                        'displayName' => 'existing',
                        'email' => 'existing@example.com',
                    ],
                    2 => [
                        'displayName' => 'new',
                        'email' => 'new@example.com',
                    ],
                    3 => [
                        'displayName' => 'new2',
                        'email' => 'new2@example.com',
                    ],
                ],
            ],
        ];
    }
}
