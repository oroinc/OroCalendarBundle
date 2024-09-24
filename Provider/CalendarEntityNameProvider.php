<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a text representation of Calendar entity.
 */
class CalendarEntityNameProvider implements EntityNameProviderInterface
{
    private const TRANSLATION_KEY = 'oro.calendar.label_not_available';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Calendar) {
            return false;
        }

        $name = $entity->getName();
        if (!$name) {
            $owner = $entity->getOwner();
            $name = null !== $owner
                ? sprintf('%s %s', $owner->getFirstName(), $owner->getLastName())
                : $this->trans(self::TRANSLATION_KEY, $locale);
        }

        return $name;
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Calendar::class, true)) {
            return false;
        }

        return sprintf(
            '(SELECT COALESCE(%1$s_c.name, CONCAT(%1$s_co.firstName, \' \', %1$s_co.lastName), %3$s)'
            . ' FROM %2$s %1$s_c LEFT JOIN %1$s_c.owner %1$s_co WHERE %1$s_c = %1$s)',
            $alias,
            Calendar::class,
            (string)(new Expr())->literal($this->trans(self::TRANSLATION_KEY, $locale))
        );
    }

    private function trans(string $key, string|Localization|null $locale): string
    {
        if ($locale instanceof Localization) {
            $locale = $locale->getLanguageCode();
        }

        return $this->translator->trans($key, [], null, $locale);
    }
}
