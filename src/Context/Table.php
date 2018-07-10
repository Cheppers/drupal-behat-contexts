<?php

namespace Cheppers\DrupalExtension\Context;

use Cheppers\DrupalExtension\Component\TableContextTrait;

class Table extends Base
{
    use TableContextTrait;

    /**
     * @When /^I click on the (?P<linkIndex>\d+)(st|nd|rd|th) link in the "(?P<columnLabel>[^"]+)" column of the (?P<rowIndex>\d+)(st|nd|rd|th) row$/
     */
    public function doClickLinkInTableCell(int $linkIndex, string $columnLabel, int $rowIndex)
    {
        $selector = 'xpath';
        $locator = sprintf(
            '//table/tbody/tr[position() = %d]/td[position() = %d]//a[position() = %d]',
            $rowIndex,
            $this->getColumnIndex($columnLabel),
            $linkIndex
        );

        $this
            ->getSession()
            ->getPage()
            ->find($selector, $locator)
            ->click();
    }
}
