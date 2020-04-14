<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var Object */
    protected $entity;

    /**
     * @return string
     */
    abstract public function getEntityFQCN();

    /**
     * @return array
     */
    abstract public function getSetDataProvider();

    protected function setUp(): void
    {
        $name         = $this->getEntityFQCN();
        $this->entity = new $name();
    }

    protected function tearDown(): void
    {
        unset($this->entity);
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if ($value !== null) {
            $propertyAccessor->setValue($this->entity, $property, $value);
        }
        $this->assertEquals($expected, $propertyAccessor->getValue($this->entity, $property));
    }

    public function testGetId()
    {
        // guard
        $this->assertNull($this->entity->getId());

        ReflectionUtil::setId($this->entity, 5);
        $this->assertEquals(5, $this->entity->getId());
    }
}
