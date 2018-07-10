<?php

namespace Cheppers\DrupalExtension\Context;

use Behat\Behat\Tester\Exception\PendingException;

class Response extends Base
{
    /**
     * @Then /^the (?P<headerName>[^\s]+) response header is empty$/
     */
    public function assertResponseHeaderIsEmpty($headerName)
    {
        throw new PendingException();
    }

    /**
     * @Then /^the (?P<headerName>[^\s]+) response header isn't exists$/
     */
    public function assertResponseHeaderIsNotExists($headerName)
    {
        throw new PendingException();
    }

    /**
     * @Then /^the (?P<headerName>[^\s]+) response header is
     *   "(?P<value_expected>.+)"$/
     */
    public function assertResponseHeaderEquals($headerName, $value_expected)
    {
        $this
            ->assertSession()
            ->responseHeaderEquals($headerName, $value_expected);
    }

    /**
     * @Then /^the response body is empty$/
     */
    public function assertResponseBodyIsEmpty()
    {
        $this
            ->assertSession()
            ->responseMatches('@^$@');
    }
}
