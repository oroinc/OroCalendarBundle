<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeeRelationManager
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var DQLNameFormatter */
    protected $dqlNameFormatter;

    public function __construct(
        ManagerRegistry $registry,
        NameFormatter $nameFormatter,
        DQLNameFormatter $dqlNameFormatter
    ) {
        $this->registry = $registry;
        $this->nameFormatter = $nameFormatter;
        $this->dqlNameFormatter = $dqlNameFormatter;
    }

    /**
     * Set related entity of the attendee.
     *
     * @param Attendee $attendee
     * @param object $relatedEntity
     *
     * @throws \InvalidArgumentException If related entity type is not supported.
     */
    public function setRelatedEntity(Attendee $attendee, $relatedEntity = null)
    {
        if ($relatedEntity instanceof User) {
            $attendee
                ->setUser($relatedEntity)
                ->setDisplayName($this->nameFormatter->format($relatedEntity))
                ->setEmail($relatedEntity->getEmail());
        } else {
            // Only User is supported as related entity of attendee.
            throw new \InvalidArgumentException(
                sprintf(
                    'Related entity must be an instance of "%s", "%s" is given.',
                    User::class,
                    is_object($relatedEntity) ? ClassUtils::getClass($relatedEntity) : gettype($relatedEntity)
                )
            );
        }
    }

    /**
     * @param Attendee $attendee
     *
     * @return object|null
     */
    public function getRelatedEntity(Attendee $attendee)
    {
        return $attendee->getUser();
    }

    /**
     * @param Attendee $attendee
     *
     * @return string
     */
    public function getDisplayName(Attendee $attendee)
    {
        if ($attendee->getUser()) {
            return $this->nameFormatter->format($attendee->getUser());
        }

        return $attendee->getDisplayName() . ($attendee->getEmail() ? (' <' . $attendee->getEmail() . '>') : '');
    }

    /**
     * Adds fullName column with text representation of attendee into the result
     */
    public function addRelatedEntityInfo(QueryBuilder $qb)
    {
        $userName = $this->dqlNameFormatter->getFormattedNameDQL('user', 'Oro\Bundle\UserBundle\Entity\User');

        $qb
            ->addSelect(sprintf('TRIM(%s) AS fullName, user.id AS userId', $userName))
            ->leftJoin('attendee.user', 'user');
    }

    /**
     * @param Attendee[]|\Traversable $attendees
     * @param Organization|null $organization
     */
    public function bindAttendees($attendees, Organization $organization = null)
    {
        $unboundAttendeesByEmail = $this->getUnboundAttendeesByEmail($attendees);
        if (!$unboundAttendeesByEmail) {
            return;
        }

        $users = $this->registry
            ->getRepository('OroUserBundle:User')
            ->findUsersByEmailsAndOrganization(array_keys($unboundAttendeesByEmail), $organization);

        $this->bindUsersToAttendees($users, $unboundAttendeesByEmail);
    }

    /**
     * @param User[]   $users
     * @param string[] $unboundAttendeesByEmail
     */
    protected function bindUsersToAttendees(array $users, array $unboundAttendeesByEmail)
    {
        foreach ($users as $user) {
            $normalizedEmail = $this->normalizeEmail($user->getEmail());
            if (isset($unboundAttendeesByEmail[$normalizedEmail])) {
                $this->bindUser($user, $unboundAttendeesByEmail[$normalizedEmail]);
                unset($unboundAttendeesByEmail[$normalizedEmail]);
            }

            foreach ($user->getEmails() as $emailEntity) {
                $normalizedEmail = $this->normalizeEmail($emailEntity->getEmail());
                if (isset($unboundAttendeesByEmail[$normalizedEmail])) {
                    $this->bindUser($user, $unboundAttendeesByEmail[$normalizedEmail]);
                    unset($unboundAttendeesByEmail[$normalizedEmail]);
                }
            }
        }
    }

    protected function bindUser(User $user, Attendee $attendee)
    {
        $attendee->setUser($user);
        if (!$attendee->getDisplayName()) {
            $attendee->setDisplayName($this->nameFormatter->format($user));
        }
    }

    /**
     * @param Attendee[]|\Traversable $attendees
     *
     * @return Attendee[]
     */
    protected function getUnboundAttendeesByEmail($attendees)
    {
        $unbound = [];
        foreach ($attendees as $attendee) {
            if (!$attendee->getEmail() || $this->getRelatedEntity($attendee)) {
                continue;
            }

            $unbound[$this->normalizeEmail($attendee->getEmail())] = $attendee;
        }

        return $unbound;
    }

    /**
     * @param string|null $email
     *
     * @return string|null
     */
    protected function normalizeEmail($email)
    {
        if (!$email) {
            return $email;
        }

        return strtolower($email);
    }
}
