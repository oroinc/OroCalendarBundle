<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Choice;

class CalendarEventTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $notificationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $tokenAccessor;

    /**
     * @var CalendarEventType
     */
    protected $type;

    protected function setUp()
    {
        $repository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->any())
            ->method('find')
            ->will($this->returnCallback(function ($id) {
                return new TestEnumValue($id, $id);
            }));

        $managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($repository));

        $this->notificationManager = $this->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new CalendarEventType(
            $this->notificationManager,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $registry
        );
    }

    public function formBuildProvider()
    {
        return [
            'with assign calendar permissions' => [true],
            'without assign calendar permissions' => [false]
        ];
    }

    /**
     * @dataProvider formBuildProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm($permissions)
    {
        $minYear = date_create('-10 year')->format('Y');
        $maxYear = date_create('+80 year')->format('Y');
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_calendar_event_assign_management')
            ->will($this->returnValue($permissions));
        $counter = 0;

        if ($permissions) {
            $builder->expects($this->at(0))
                ->method('add')
                ->with(
                    'calendar',
                    'oro_user_select',
                    [
                        'label' => 'oro.calendar.owner.label',
                        'required' => true,
                        'autocomplete_alias' => 'user_calendars',
                        'entity_class' => Calendar::class,
                        'configs' => array(
                            'entity_name' => 'OroCalendarBundle:Calendar',
                            'excludeCurrent' => true,
                            'component' => 'acl-user-autocomplete',
                            'permission' => 'VIEW',
                            'placeholder' => 'oro.calendar.form.choose_user_to_add_calendar',
                            'result_template_twig' => 'OroCalendarBundle:Calendar:Autocomplete/result.html.twig',
                            'selection_template_twig' => 'OroCalendarBundle:Calendar:Autocomplete/selection.html.twig',
                        ),

                        'grid_name' => 'users-calendar-select-grid-exclude-owner',
                        'random_id' => false
                    ]
                )
                ->will($this->returnSelf());
            $counter = $counter + 2;
        }
        $builder->expects($this->at($counter))
            ->method('add')
            ->with(
                'title',
                'text',
                ['required' => true, 'label' => 'oro.calendar.calendarevent.title.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 1))
            ->method('add')
            ->with(
                'description',
                'oro_resizeable_rich_text',
                ['required' => false, 'label' => 'oro.calendar.calendarevent.description.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 2))
            ->method('add')
            ->with(
                'start',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.start.label',
                    'attr'     => ['class' => 'start'],
                    'years'    => [$minYear, $maxYear],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 3))
            ->method('add')
            ->with(
                'end',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.end.label',
                    'attr'     => ['class' => 'end'],
                    'years'    => [$minYear, $maxYear],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 4))
            ->method('add')
            ->with(
                'allDay',
                'checkbox',
                ['required' => false, 'label' => 'oro.calendar.calendarevent.all_day.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 5))
            ->method('add')
            ->with(
                'backgroundColor',
                'oro_simple_color_picker',
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.calendarevent.background_color.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.calendarevent.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 6))
            ->method('add')
            ->with(
                'reminders',
                'oro_reminder_collection',
                ['required' => false, 'label' => 'oro.reminder.entity_plural_label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at($counter + 7))
            ->method('add')
            ->with(
                'attendees',
                'oro_calendar_event_attendees_select',
                [
                    'required' => false,
                    'label' => 'oro.calendar.calendarevent.attendees.label',
                    'layout_template' => false,
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at($counter + 8))
            ->method('add')
            ->with(
                'notifyAttendees',
                'hidden',
                [
                    'mapped' => false,
                    'constraints' => [new Choice()]
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at($counter + 9))
            ->method('add')
            ->with(
                'recurrence',
                'oro_calendar_event_recurrence',
                [
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, ['layout_template' => false]);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'allow_change_calendar' => false,
                    'layout_template'       => false,
                    'data_class'            => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                    'intention'             => 'calendar_event',
                    'csrf_protection'       => false,
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_event', $this->type->getName());
    }
}
