<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroCalendarBundle_Entity_SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * System calendar entity
 * @mixin OroCalendarBundle_Entity_SystemCalendar
 */
#[ORM\Entity(repositoryClass: SystemCalendarRepository::class)]
#[ORM\Table(name: 'oro_system_calendar')]
#[ORM\Index(columns: ['updated_at'], name: 'oro_system_calendar_up_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-calendar'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class SystemCalendar implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    const CALENDAR_ALIAS        = 'system';
    const PUBLIC_CALENDAR_ALIAS = 'public';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\Column(name: 'background_color', type: Types::STRING, length: 7, nullable: true)]
    protected ?string $backgroundColor = null;

    #[ORM\Column(name: 'is_public', type: Types::BOOLEAN)]
    protected ?bool $public = false;

    /**
     * @var Collection<int, CalendarEvent>
     */
    #[ORM\OneToMany(mappedBy: 'systemCalendar', targetEntity: CalendarEvent::class, cascade: ['persist'])]
    protected ?Collection $events = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

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
    #[\Override]
    public function __toString()
    {
        return (string)$this->name;
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
     * Gets the calendar name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the calendar name.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets a background color of this calendar.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, e.g. #FF0000.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of this calendar.
     *
     * @param string|null $backgroundColor The color in hex format, e.g. #FF0000.
     *                                     Set it to null to allow UI to calculate the background color automatically.
     *
     * @return self
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * Gets a flag indicates that the calendar is available for all
     * users regardless of which organization they belong to.
     * Public calendars are available to all organizations.
     * Private calendars are available only to users inside one organization.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Sets a flag indicates whether the calendar is public or not.
     *
     * @param  bool $public
     *
     * @return self
     */
    public function setPublic($public)
    {
        $this->public = (bool)$public;

        return $this;
    }

    /**
     * Gets all events of the calendar.
     *
     * @return CalendarEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Adds an event to the calendar.
     *
     * @param  CalendarEvent $event
     *
     * @return self
     */
    public function addEvent(CalendarEvent $event)
    {
        $this->events[] = $event;

        $event->setSystemCalendar($this);

        return $this;
    }

    /**
     * Sets owning organization
     * Public calendars don't belong to any organization
     *
     * @param Organization|null $organization
     *
     * @return self
     */
    public function setOrganization(Organization $organization = null)
    {
        if ($organization && $this->isPublic()) {
            return $this;
        }

        $this->organization = $organization;

        return $this;
    }

    /**
     * Gets owning organization
     * Public calendars don't belong to any organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Gets a creation date/time
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets a creation date/time
     *
     * @param \DateTime $createdAt
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Gets a modification date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets a modification date/time
     *
     * @param \DateTime $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Pre persist event handler
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
