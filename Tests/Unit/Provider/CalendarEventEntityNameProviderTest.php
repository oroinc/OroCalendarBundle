<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Provider\CalendarEventEntityNameProvider;
use PHPUnit\Framework\TestCase;

class CalendarEventEntityNameProviderTest extends TestCase
{
    /**
     * @var CalendarEventEntityNameProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new CalendarEventEntityNameProvider();
    }

    /**
     * @dataProvider nameDataProvider
     * @param string $format
     * @param object $entity
     * @param string $expected
     */
    public function testGetName($format, $entity, $expected)
    {
        $this->assertEquals($expected, $this->provider->getName($format, 'en', $entity));
    }

    /**
     * @return \Generator
     */
    public function nameDataProvider(): ?\Generator
    {
        foreach ($this->getFormats() as $format) {
            yield [$format, new \stdClass(), false];
            yield [$format, (new CalendarEvent())->setTitle('My Event'), 'My Event'];
        }
    }

    /**
     * @dataProvider dqlNameDataProvider
     * @param string $format
     * @param string $entityClass
     * @param string $expected
     */
    public function testGetNameDQL($format, $entityClass, $expected)
    {
        $this->assertEquals($expected, $this->provider->getNameDQL($format, 'en', $entityClass, 'alias'));
    }

    /**
     * @return \Generator
     */
    public function dqlNameDataProvider(): ?\Generator
    {
        foreach ($this->getFormats() as $format) {
            yield [$format, \stdClass::class, false];
            yield [$format, CalendarEvent::class, 'alias.title'];
        }
    }

    private function getFormats(): array
    {
        return [
            CalendarEventEntityNameProvider::FULL,
            CalendarEventEntityNameProvider::SHORT
        ];
    }
}
