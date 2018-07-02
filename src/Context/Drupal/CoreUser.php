<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Drupal\user\Entity\User;
use PHPUnit_Framework_Assert as Assert;

class CoreUser extends Base
{
    use CoreContentEntityContextTrait;

    /**
     * @Given user:
     */
    public function doAccountCreate(TableNode $fieldValues)
    {
        $entityTypeId = 'user';
        $fieldValues = $fieldValues->getRowsHash();
        $fieldValues = $this->keyValuePairsToNestedArray($fieldValues);

        $this->createContentEntity($entityTypeId, $this->keyValuePairsToNestedArray($fieldValues));
    }

    /**
     * @Given /^the "(?P<accountLabel>[^"]+)" account is deleted with "(?P<cancelMethodLabel>[^"]+)" cancel method$/
     */
    public function doAccountIsDeletedWithCancelMethod(string $accountLabel, string $cancelMethodLabel)
    {
        $cancelMethodId = $this->getUserCancelMethodIdByLabel($cancelMethodLabel);
        Assert::assertNotEmpty(
            $cancelMethodId,
            sprintf('User cancel method is not exists: "%s"', $cancelMethodLabel)
        );

        /** @var \Drupal\user\UserInterface $account */
        $account = $this->getContentEntityByLabel('user', $accountLabel);
        Assert::assertNotEmpty(
            $account,
            sprintf('User is not exists: "%s"', $accountLabel)
        );

        user_cancel(
            [],
            $account->id(),
            $cancelMethodId
        );

        $batch =& batch_get();
        $batch['progressive'] = false;
        batch_process();

        // @todo Not every cancel method deletes the account.
        $account = User::load($account->id());
        Assert::assertNull(
            $account,
            "User cancelling with '$cancelMethodLabel' failed."
        );
    }

    protected function getUserCancelMethodIdByLabel(string $label): string
    {
        $cancelMethods = user_cancel_methods();

        return (string) array_search($label, $cancelMethods['#options']);
    }
}
