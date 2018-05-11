<?php

namespace Cheppers\DrupalExtension\Context\PayPal;

use Behat\Mink\Exception\ElementNotFoundException;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit_Framework_Assert as Assert;

class CheckoutNow extends Base
{
    /**
     * @Then /^the PayPal cart total is "(?P<amount>[^"]+)" (?P<currencyCode>[^\s]+)$/
     */
    public function assertCartTotalIs(string $amount, string $currencyCode)
    {
        $page = $this
            ->getSession()
            ->getPage();

        $selector = 'css';
        $locator = 'cart-wrapper format-currency';
        $formattedElement = $page->find($selector, $locator);
        if (!$formattedElement) {
            throw new ElementNotFoundException(
                $this->getSession(),
                null,
                $selector,
                $locator
            );
        }

        $formatted = trim($formattedElement->getText());
        $formattedParts = preg_split('/\s+/', $formatted, 2);

        $symbol = '';
        $matches = null;
        preg_match('/^(?P<symbol>[^0-9])/', $amount, $matches);
        if ($matches) {
            $symbol = $matches['symbol'];
            $amount = mb_substr($amount, mb_strlen($symbol));
        }

        $amountDot = str_replace(',', '.', $amount);
        $amountComma = str_replace('.', ',', $amount);
        $expectedVariants = [
            "{$symbol}{$amountDot} $currencyCode",
            "{$symbol}{$amountComma} {$currencyCode}",
            "{$amountDot} $currencyCode",
            "{$amountComma} {$currencyCode}",
        ];

        Assert::assertContains(
            "{$formattedParts[0]} {$formattedParts[1]}",
            $expectedVariants
        );
    }

    /**
     * @When I login to PayPal with the :account credentials
     */
    public function doLogin(string $account)
    {
        $emailEnvVarName = $this->loginCredentialEnvVarName($account, 'email');
        $email = getenv($emailEnvVarName);

        $passwordEnvVarName = $this->loginCredentialEnvVarName($account, 'password');
        $password = getenv($passwordEnvVarName);

        $page = $this
            ->getSession()
            ->getPage();

        $payPalIframe = $page->find('xpath', '//iframe');

        $this
            ->getSession()
            ->getDriver()
            ->switchToIFrame($payPalIframe->getAttribute('name'));

        $page
            ->findById('email')
            ->setValue($email);

        $page
            ->findById('password')
            ->setValue($password);

        $page
            ->findById('btnLogin')
            ->click();
    }

    /**
     * @When I click on the Continue button on the PayPal
     */
    public function doLoginContinue()
    {
        $this->getSession()->switchToIFrame();

        /** @var \Behat\Mink\Element\NodeElement $doConfirm */
        $doConfirm = null;
        $iteration = 0;
        while ($doConfirm === null && $iteration <= 5) {
            ++$iteration;
            sleep(3);
            $doConfirm = $this
                ->getSession()
                ->getPage()
                ->findById('confirmButtonTop');
        }

        $doConfirm->click();

        $this
            ->getSession()
            ->wait(10000, "typeof Drupal !== 'undefined'");
    }

    /**
     * @When I click on the Cancel link on the PayPal
     */
    public function doCancel()
    {
        $this
            ->getSession()
            ->getPage()
            ->clickLink('cancelLink');
    }

    protected function loginCredentialEnvVarName(string $name, $part): string
    {
        $envVarName = "app_paypal_credential_{$name}_{$part}";

        return strtoupper(preg_replace('/[^a-z0-9]/ui', '_', $envVarName));
    }
}
