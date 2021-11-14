<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Provider\CalendarEventEntityNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

class CalendarEventEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventEntityNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new CalendarEventEntityNameProvider();
    }

    /**
     * @dataProvider nameDataProvider
     */
    public function testGetName(string $format, object $entity, string|false $expected)
    {
        $this->assertSame($expected, $this->provider->getName($format, 'en', $entity));
    }

    public function nameDataProvider(): array
    {
        $result = [];
        foreach ($this->getFormats() as $format) {
            $result[] = [$format, new \stdClass(), false];
            $result[] = [$format, (new CalendarEvent())->setTitle('My Event'), 'My Event'];
        }

        return $result;
    }

    /**
     * @dataProvider dqlNameDataProvider
     */
    public function testGetNameDQL(string $format, string $entityClass, string|false $expected)
    {
        $this->assertSame($expected, $this->provider->getNameDQL($format, 'en', $entityClass, 'alias'));
    }

    public function dqlNameDataProvider(): array
    {
        $result = [];
        foreach ($this->getFormats() as $format) {
            $result[] = [$format, \stdClass::class, false];
            $result[] = [$format, CalendarEvent::class, 'alias.title'];
        }

        return $result;
    }

    private function getFormats(): array
    {
        return [
            EntityNameProviderInterface::FULL,
            EntityNameProviderInterface::SHORT
        ];
    }
}
