<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventAttendeesSelectType;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\CalendarBundle\Form\Type\RecurrenceFormType;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderCollectionType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Choice;

class CalendarEventTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var CalendarEventType */
    private $type;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return new TestEnumValue($id, $id);
            });

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->willReturn($repository);

        $this->type = new CalendarEventType(
            $this->createMock(NotificationManager::class),
            $this->authorizationChecker,
            $this->createMock(TokenAccessorInterface::class),
            $registry
        );
    }

    public function formBuildProvider(): array
    {
        return [
            'with assign calendar permissions'    => [true],
            'without assign calendar permissions' => [false]
        ];
    }

    /**
     * @dataProvider formBuildProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm(bool $permissions)
    {
        $minYear = date_create('-10 year')->format('Y');
        $maxYear = date_create('+80 year')->format('Y');
        $builder = $this->createMock(FormBuilder::class);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_calendar_event_assign_management')
            ->willReturn($permissions);

        $formFields = [];

        if ($permissions) {
            $formFields[] = [
                'calendar',
                UserSelectType::class,
                [
                    'label'              => 'oro.calendar.owner.label',
                    'required'           => true,
                    'autocomplete_alias' => 'user_calendars',
                    'entity_class'       => Calendar::class,
                    'configs'            => [
                        'entity_name'             => Calendar::class,
                        'excludeCurrent'          => true,
                        'component'               => 'acl-user-autocomplete',
                        'permission'              => 'VIEW',
                        'placeholder'             => 'oro.calendar.form.choose_user_to_add_calendar',
                        'result_template_twig'    => '@OroCalendar/Calendar/Autocomplete/result.html.twig',
                        'selection_template_twig' => '@OroCalendar/Calendar/Autocomplete/selection.html.twig',
                    ],
                    'grid_name'          => 'users-calendar-select-grid-exclude-owner',
                    'random_id'          => false
                ]
            ];
        }
        $formFields[] = [
            'title',
            TextType::class,
            ['required' => true, 'label' => 'oro.calendar.calendarevent.title.label']
        ];
        $formFields[] = [
            'description',
            OroResizeableRichTextType::class,
            ['required' => false, 'label' => 'oro.calendar.calendarevent.description.label']
        ];
        $formFields[] = [
            'start',
            OroDateTimeType::class,
            [
                'required' => true,
                'label'    => 'oro.calendar.calendarevent.start.label',
                'attr'     => ['class' => 'start'],
                'years'    => [$minYear, $maxYear],
            ]
        ];
        $formFields[] = [
            'end',
            OroDateTimeType::class,
            [
                'required' => true,
                'label'    => 'oro.calendar.calendarevent.end.label',
                'attr'     => ['class' => 'end'],
                'years'    => [$minYear, $maxYear],
            ]
        ];
        $formFields[] = [
            'allDay',
            CheckboxType::class,
            ['required' => false, 'label' => 'oro.calendar.calendarevent.all_day.label']
        ];
        $formFields[] = [
            'backgroundColor',
            OroSimpleColorPickerType::class,
            [
                'required'           => false,
                'label'              => 'oro.calendar.calendarevent.background_color.label',
                'color_schema'       => 'oro_calendar.event_colors',
                'empty_value'        => 'oro.calendar.calendarevent.no_color',
                'allow_empty_color'  => true,
                'allow_custom_color' => true
            ]
        ];
        $formFields[] = [
            'reminders',
            ReminderCollectionType::class,
            ['required' => false, 'label' => 'oro.reminder.entity_plural_label']
        ];
        $formFields[] = [
            'attendees',
            CalendarEventAttendeesSelectType::class,
            [
                'required'        => false,
                'label'           => 'oro.calendar.calendarevent.attendees.label',
                'layout_template' => false,
            ]
        ];
        $formFields[] = [
            'notifyAttendees',
            HiddenType::class,
            [
                'mapped'      => false,
                'constraints' => [new Choice()]
            ]
        ];
        $formFields[] = [
            'recurrence',
            RecurrenceFormType::class,
            [
                'required' => false,
            ]
        ];

        $builder->expects($this->exactly(count($formFields)))
            ->method('add')
            ->withConsecutive(...$formFields)
            ->willReturnSelf();

        $this->type->buildForm($builder, ['layout_template' => false]);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'allow_change_calendar' => false,
                    'layout_template'       => false,
                    'data_class'            => CalendarEvent::class,
                    'csrf_token_id'         => 'calendar_event',
                    'csrf_protection'       => false,
                ]
            );

        $this->type->configureOptions($resolver);
    }
}
