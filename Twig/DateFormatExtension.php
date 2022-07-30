<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides twig functions to format date ranges:
 *   - calendar_date_range
 *   - calendar_date_range_organization
 */
class DateFormatExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return DateTimeFormatterInterface
     */
    protected function getDateTimeFormatter()
    {
        return $this->container->get('oro_locale.formatter.date_time');
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get('oro_config.global');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'calendar_date_range',
                [$this, 'formatCalendarDateRange']
            ),
            new TwigFunction(
                'calendar_date_range_organization',
                [$this, 'formatCalendarDateRangeOrganization']
            )
        ];
    }

    /**
     * Returns a string represents a range between $startDate and $endDate, formatted according the given parameters
     * Examples:
     *      $endDate is not specified
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate equals to $endDate
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate and $endDate are the same day
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:00pm – 5:30pm - when $skipTime = false
     *      $startDate and $endDate are different days
     *          Thu Oct 17, 2013 5:00pm – Thu Oct 18, 2013 5:00pm - when $skipTime = false
     *          Thu Oct 17, 2013 – Thu Oct 18, 2013 - when $skipTime = true
     *
     * @param \DateTime|null             $startDate
     * @param \DateTime|null             $endDate
     * @param bool                       $skipTime
     * @param string|int|null            $dateType \IntlDateFormatter constant or it's string name
     * @param string|int|null            $timeType \IntlDateFormatter constant or it's string name
     * @param string|null                $locale
     * @param string|null                $timeZone
     * @param OrganizationInterface|null $organization
     *
     * @return string
     */
    public function formatCalendarDateRangeOrganization(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null,
        OrganizationInterface $organization = null
    ) {
        // Get localization settings from user scope
        if ($organization instanceof OrganizationInterface) {
            [$locale, $timeZone] = $this->getOrganizationLocaleSettings($organization);
        }

        return $this->formatCalendarDateRange(
            $startDate,
            $endDate,
            $skipTime,
            $dateType,
            $timeType,
            $locale,
            $timeZone
        );
    }

    /**
     * Returns a string represents a range between $startDate and $endDate, formatted according the given parameters
     * Examples:
     *      $endDate is not specified
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate equals to $endDate
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:30pm - when $skipTime = false
     *      $startDate and $endDate are the same day
     *          Thu Oct 17, 2013 - when $skipTime = true
     *          Thu Oct 17, 2013 5:00pm – 5:30pm - when $skipTime = false
     *      $startDate and $endDate are different days
     *          Thu Oct 17, 2013 5:00pm – Thu Oct 18, 2013 5:00pm - when $skipTime = false
     *          Thu Oct 17, 2013 – Thu Oct 18, 2013 - when $skipTime = true
     *
     * @param \DateTime|null    $startDate
     * @param \DateTime|null    $endDate
     * @param bool              $skipTime
     * @param string|int|null   $dateType \IntlDateFormatter constant or it's string name
     * @param string|int|null   $timeType \IntlDateFormatter constant or it's string name
     * @param string|null       $locale
     * @param string|null       $timeZone
     *
     * @return string
     */
    public function formatCalendarDateRange(
        \DateTime $startDate = null,
        \DateTime $endDate = null,
        $skipTime = false,
        $dateType = null,
        $timeType = null,
        $locale = null,
        $timeZone = null
    ) {
        if (is_null($startDate)) {
            // exit because nothing to format.
            // We have to accept null as $startDate because the validator of email templates calls functions
            // with empty arguments
            return '';
        }

        $formatter = $this->getDateTimeFormatter();
        // check if $endDate is not specified or $startDate equals to $endDate
        if (is_null($endDate) || $startDate == $endDate) {
            return $skipTime
                ? $formatter->formatDate($startDate, $dateType, $locale, $timeZone)
                : $formatter->format($startDate, $dateType, $timeType, $locale, $timeZone);
        }

        // check if $startDate and $endDate are the same day
        if ($startDate->format('Ymd') == $endDate->format('Ymd')) {
            if ($skipTime) {
                return $formatter->formatDate($startDate, $dateType, $locale, $timeZone);
            }

            return sprintf(
                '%s %s - %s',
                $formatter->formatDate($startDate, $dateType, $locale, $timeZone),
                $formatter->formatTime($startDate, $timeType, $locale, $timeZone),
                $formatter->formatTime($endDate, $timeType, $locale, $timeZone)
            );
        }

        // $startDate and $endDate are different days
        if ($skipTime) {
            return sprintf(
                '%s - %s',
                $formatter->formatDate($startDate, $dateType, $locale, $timeZone),
                $formatter->formatDate($endDate, $dateType, $locale, $timeZone)
            );
        }

        return sprintf(
            '%s - %s',
            $formatter->format($startDate, $dateType, $timeType, $locale, $timeZone),
            $formatter->format($endDate, $dateType, $timeType, $locale, $timeZone)
        );
    }

    /**
     * @param OrganizationInterface $organization
     *
     * @return array ['locale', 'timezone']
     */
    protected function getOrganizationLocaleSettings(OrganizationInterface $organization)
    {
        $configManager = $this->getConfigManager();

        $localizationId = $configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));
        $timeZone = $configManager->get('oro_locale.timezone');

        return [$this->getFormattingCode((int) $localizationId), $timeZone];
    }

    /**
     * @param int $localizationId
     * @return string
     */
    protected function getFormattingCode(int $localizationId)
    {
        $localizationData = $this->container->get('oro_locale.manager.localization')
            ->getLocalizationData($localizationId);

        return $localizationData['formattingCode'] ?? Configuration::DEFAULT_LOCALE;
    }

    /**
     * {@inheritdoc]
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_locale.formatter.date_time' => DateTimeFormatterInterface::class,
            'oro_config.global' => ConfigManager::class,
            'oro_locale.manager.localization' => LocalizationManager::class,
        ];
    }
}
