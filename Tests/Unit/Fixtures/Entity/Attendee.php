<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\CalendarBundle\Entity\Attendee as BaseAttendee;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;

class Attendee extends BaseAttendee
{
    /**
     * @var EnumOptionInterface|null
     */
    protected $status;

    /**
     * @var EnumOptionInterface|null
     */
    protected $type;

    /**
     * @param integer|null $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @param EnumOptionInterface|null $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return null|EnumOptionInterface
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return null|EnumOptionInterface
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param EnumOptionInterface|null $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
