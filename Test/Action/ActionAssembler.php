<?php

namespace Oro\Bundle\CalendarBundle\Test\Action;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Action\ActionAssembler as BaseActionAssembler;
use Oro\Component\Action\Model\ContextAccessor as ActionContextAccessor;

use Oro\Component\ConfigExpression\ContextAccessor as ConditionContextAccessor;
use Oro\Component\ConfigExpression\ContextAccessorInterface as ConditionContextAccessorInterface;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;
use Oro\Component\ConfigExpression\Extension\Core\CoreExtension;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Test\Action\ConfigurationPass\EvaluateExpressionsConfigurationPass;
use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;

/**
 * @internal
 */
class ActionAssembler extends BaseActionAssembler
{
    /**
     * @param ContextInterface $context
     * @return static
     */
    public static function create(ContextInterface $context)
    {
        $result = new static(
            self::createActionFactory(),
            self::createConditionFactory()
        );

        $result->addConfigurationPass(
            self::createEvaluateExpressionsConfigurationPass($context)
        );

        return $result;
    }

    /**
     * @return ActionFactory
     */
    protected static function createActionFactory()
    {
        $actionContextAccessor = self::createActionContextAccessor();

        $factory = new ActionFactory(self::createEventDispatcher());

        $factory->addExtension(new Api\Rest\CalendarEvent\ActionExtension($actionContextAccessor));
        $factory->addExtension(new Api\Rest\CalendarConnection\ActionExtension($actionContextAccessor));
        $factory->addExtension(new Api\Rest\ActionExtension($actionContextAccessor));
        $factory->addExtension(new Extension\CoreExtension($actionContextAccessor));

        return $factory;
    }

    /**
     * @return ActionContextAccessor
     */
    protected static function createActionContextAccessor()
    {
        return new ActionContextAccessor();
    }

    /**
     * @return ConditionContextAccessorInterface
     */
    protected static function createConditionContextAccessor()
    {
        return new ConditionContextAccessor();
    }

    /**
     * @return EventDispatcherInterface
     */
    protected static function createEventDispatcher()
    {
        return new EventDispatcher();
    }

    /**
     * @return ConditionFactory
     */
    protected static function createConditionFactory()
    {
        $result = new ConditionFactory(self::createConditionContextAccessor());
        $result->addExtension(new CoreExtension());

        return $result;
    }

    /**
     * @param ContextInterface $context
     * @return ConfigurationPassInterface
     */
    protected static function createEvaluateExpressionsConfigurationPass(ContextInterface $context)
    {
        return new EvaluateExpressionsConfigurationPass(
            self::createExpressionLanguage(),
            $context
        );
    }

    /**
     * @return ExpressionLanguage
     */
    protected static function createExpressionLanguage()
    {
        $result = new ExpressionLanguage();

        $result->addFunction(
            new ExpressionFunction(
                'reference',
                function () {
                    return '$context->getReference($name)';
                },
                function (array $variables, $value) {
                    /** @var ContextInterface $context */
                    $context = $variables['context'];

                    return $context->getReference($value);
                }
            )
        );

        $result->addFunction(
            new ExpressionFunction(
                'path',
                function ($input) {
                    return $input;
                },
                function (array $variables, $value) {
                    return new PropertyPath($value);
                }
            )
        );

        $result->addFunction(
            new ExpressionFunction(
                'attendeeByEmail',
                function ($input) {
                    return $input;
                },
                function (array $variables, $eventReference, $attendeeEmail) {
                    /** @var ContextInterface $context */
                    $context = $variables['context'];

                    /** @var CalendarEvent $event */
                    $event = $context->getReference($eventReference);
                    foreach ($event->getAttendees() as $attendee) {
                        if ($attendee->getEmail() == $attendeeEmail) {
                            return $attendee;
                        }
                    }

                    throw new \RuntimeException(
                        sprintf(
                            'Expression function attendeeByEmail("%s", "%s") cannot find attendee by email "%s".',
                            $eventReference,
                            $attendeeEmail,
                            $attendeeEmail
                        )
                    );
                }
            )
        );

        return $result;
    }
}
