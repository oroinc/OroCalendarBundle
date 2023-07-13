<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Calendar entity
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository")
 * @ORM\Table(name="oro_calendar")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-calendar"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class Calendar implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    const CALENDAR_ALIAS = 'user';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var ArrayCollection|CalendarEvent[]
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="calendar", cascade={"persist"})
     */
    protected $events;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return empty($this->name)
            ? ($this->owner ? (string)$this->owner : '[default]')
            : $this->name;
    }

    /**
     * Gets the calendar id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets calendar name.
     * Usually user's default calendar has no name and this method returns null.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets calendar name.
     *
     * @param string|null $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets owning user for this calendar
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Sets owning user for this calendar
     *
     * @param User $owningUser
     *
     * @return self
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Gets all events of this calendar.
     *
     * @return CalendarEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Adds an event to this calendar.
     *
     * @param  CalendarEvent $event
     *
     * @return self
     */
    public function addEvent(CalendarEvent $event)
    {
        $this->events[] = $event;

        $event->setCalendar($this);

        return $this;
    }

    /**
     * Sets owning organization
     *
     * @param Organization|null $organization
     *
     * @return self
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Gets owning organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
