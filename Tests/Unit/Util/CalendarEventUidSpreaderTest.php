<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Util;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException;
use Oro\Bundle\CalendarBundle\Util\CalendarEventUidSpreader;

class CalendarEventUidSpreaderTest extends \PHPUnit_Framework_TestCase
{
    const UID = 'MOCK-UUID-123456';

    /** @var CalendarEventUidSpreader */
    private $spreader;

    protected function setUp()
    {
        $this->spreader = new CalendarEventUidSpreader();
    }

    public function testSpreadUidToChildren()
    {
        $child1 = new CalendarEvent();
        $child2 = new CalendarEvent();

        $parent = new CalendarEvent();
        $parent->addChildEvent($child1)
            ->addChildEvent($child2)
            ->setUid(self::UID);

        $this->spreader->process($parent);

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child1->getUid());
        $this->assertSame(self::UID, $child2->getUid());
    }

    public function testSpreadUidToParent()
    {
        $child = new CalendarEvent();
        $child->setUid(self::UID);

        $parent = new CalendarEvent();
        $parent->addChildEvent($child);

        $this->spreader->process($child);

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child->getUid());
    }

    public function testSpreadUidToParentAndAllItsChildren()
    {
        $child1 = new CalendarEvent();
        $child1->setUid(self::UID);
        $child2 = new CalendarEvent();
        $child3 = new CalendarEvent();

        $parent = new CalendarEvent();
        $parent->addChildEvent($child1)
            ->addChildEvent($child2)
            ->addChildEvent($child3);

        $this->spreader->process($child1);

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child1->getUid());
        $this->assertSame(self::UID, $child2->getUid());
        $this->assertSame(self::UID, $child3->getUid());
    }

    public function testSpreadUidThrowExceptionIfUidAlreadySet()
    {
        $child1 = new CalendarEvent();
        $child1->setUid(self::UID);
        $child2 = new CalendarEvent();
        $child3 = new CalendarEvent();
        $child3->setUid(self::UID);

        $this->expectException(UidAlreadySetException::class);

        $parent = new CalendarEvent();
        $parent->addChildEvent($child1)
            ->addChildEvent($child2)
            ->addChildEvent($child3);

        $this->spreader->process($child1);
    }
}
