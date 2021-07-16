<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarEntityNameProvider implements EntityNameProviderInterface
{
    const NOT_AVAILABLE_TRANSLATION_KEY = 'oro.calendar.label_not_available';

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /** {@inheritdoc} */
    public function getName($format, $locale, $entity)
    {
        if (! $entity instanceof Calendar) {
            return false;
        }

        if (empty($entity->getName())) {
            return !empty($entity->getOwner()) ? $entity->getOwner()->getFullName() : $this->translator->trans(
                static::NOT_AVAILABLE_TRANSLATION_KEY,
                [],
                null,
                $locale
            );
        } else {
            return $entity->getName();
        }
    }

    /** {@inheritdoc} */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }
}
