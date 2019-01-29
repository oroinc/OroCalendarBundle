<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\UIBundle\Tests\Behat\Element\UiDialog;

class CalendarEventInfo extends UiDialog
{
    /**
     * @var array
     */
    protected $calendarItemInfo;

    /**
     * @param string $label
     */
    public function get($label)
    {
        self::assertArrayHasKey(
            $label,
            $this->calendarItemInfo,
            sprintf(
                'Calendar event has no "%s" label. Available labels "%s"',
                $label,
                implode(', ', array_keys($this->calendarItemInfo))
            )
        );

        return $this->calendarItemInfo[$label];
    }

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $dataGroup = $this->findAll('css', ".attribute-item");
        /** @var NodeElement $group */
        foreach ($dataGroup as $group) {
            $label = $group->find('css', 'label.attribute-item__term')->getText();
            $value = $group->find('css', '.attribute-item__description > div')->getText();

            if (strtotime(trim($value))) {
                $value = new \DateTime($value);
            }

            $this->calendarItemInfo[$label] = $value;
        }
    }
}
