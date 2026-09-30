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

    #[\Override]
    protected function init()
    {
        // The dialog is read as soon as it opens, and rows whose label is not rendered yet would share one empty key.
        // Thus read again while a label is empty, and at the end accept the rows that did render. Then one row
        // that never fills in cannot leave the whole set empty.
        $this->calendarItemInfo = $this->spin(
            static fn (self $element) => $element->collectItemInfo(false),
            5
        ) ?? $this->collectItemInfo(true);
    }

    /**
     * @param bool $tolerant Skip a row that is not fully rendered instead of refusing the read
     * @return array|null Null when a row is not complete and another read can help
     */
    private function collectItemInfo(bool $tolerant): ?array
    {
        $info = [];

        /** @var NodeElement $group */
        foreach ($this->findAll('css', '.attribute-item') as $group) {
            $label = $group->find('css', 'label.attribute-item__term');
            $labelText = null === $label ? '' : trim($label->getText());

            if ('' === $labelText) {
                if (!$tolerant) {
                    return null;
                }

                continue;
            }

            // renderAttribute() writes the value directly into .attribute-item__description, so a row without
            // a value has no inner <div>. This is a normal state, not a partial render worth a wait.
            $value = $group->find('css', '.attribute-item__description > div');
            $valueText = null === $value ? '' : $value->getText();

            $info[$labelText] = strtotime(trim($valueText)) ? new \DateTime($valueText) : $valueText;
        }

        if (!$info) {
            return $tolerant ? [] : null;
        }

        return $info;
    }
}
