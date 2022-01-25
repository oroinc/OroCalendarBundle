<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CalendarBundle\DependencyInjection\Compiler\CalendarProviderPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class CalendarProviderPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $manager;

    private CalendarProviderPass $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->manager = $this->container->register('oro_calendar.calendar_manager');

        $this->compiler = new CalendarProviderPass();
    }

    public function testProcessWhenNoTaggedServices(): void
    {
        $this->compiler->process($this->container);

        self::assertEquals([], $this->manager->getArgument('$providerAliases'));

        $serviceLocatorReference = $this->manager->getArgument('$providerContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessWithoutAliasAttribute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "alias" is required for "oro_calendar.calendar_provider" tag. Service: "provider_1".'
        );

        $this->container->setDefinition('provider_1', new Definition())
            ->addTag('oro_calendar.calendar_provider');

        $this->compiler->process($this->container);
    }

    public function testProcess(): void
    {
        $this->container->setDefinition('provider_1', new Definition())
            ->addTag('oro_calendar.calendar_provider', ['alias' => 'item1']);
        $this->container->setDefinition('provider_2', new Definition())
            ->addTag('oro_calendar.calendar_provider', ['alias' => 'item2', 'priority' => -10]);
        $this->container->setDefinition('provider_3', new Definition())
            ->addTag('oro_calendar.calendar_provider', ['alias' => 'item3', 'priority' => 10]);
        // override by alias
        $this->container->setDefinition('provider_4', new Definition())
            ->addTag('oro_calendar.calendar_provider', ['alias' => 'item1', 'priority' => -10]);
        // should be skipped by priority
        $this->container->setDefinition('provider_5', new Definition())
            ->addTag('oro_calendar.calendar_provider', ['alias' => 'item2']);

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                'item2',
                'item1',
                'item3'
            ],
            $this->manager->getArgument('$providerAliases')
        );

        $serviceLocatorReference = $this->manager->getArgument('$providerContainer');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'item2' => new ServiceClosureArgument(new Reference('provider_2')),
                'item1' => new ServiceClosureArgument(new Reference('provider_4')),
                'item3' => new ServiceClosureArgument(new Reference('provider_3')),
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
