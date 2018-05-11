<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use PHPUnit_Framework_Assert as Assert;

class CommerceOrder extends Base
{
    use CoreContentEntityContextTrait;

    /**
     * @Given /^the "(?P<accountName>[^"]*)" asks a total refund for the last completed order$/
     */
    public function doPaymentRefund(string $accountName)
    {
        /** @var \Drupal\user\UserInterface $account */
        $account = $this->getContentEntityByLabel('user', $accountName);
        Assert::assertNotEmpty($account, "User '$accountName' doesn't exists");

        $order = $this->getLastCompletedCommerceOrder($account->id());
        Assert::assertNotEmpty($order, "User '$accountName' has no any completed order");

        $payments = $this->getPayments($order->id(), ['completed', 'partially_refunded']);
        foreach ($payments as $payment) {
            $payment
                ->setRefundedAmount($payment->getAmount())
                ->setState('refunded')
                ->save();
        }
    }

    protected function getLastCompletedCommerceOrder(int $ownerId): ?OrderInterface
    {
        $orderIds = \Drupal::entityTypeManager()
            ->getStorage('commerce_order')
            ->getQuery()
            ->condition('state', 'completed')
            ->condition('uid', $ownerId)
            ->sort('completed', 'DESC')
            ->range(0, 1)
            ->execute();

        return $orderIds ? Order::load(reset($orderIds)) : null;
    }

    /**
     * @return \Drupal\commerce_payment\Entity\PaymentInterface[]
     */
    protected function getPayments(int $orderId, array $state = []): array
    {
        $properties = [
            'order_id' => $orderId,
        ];

        if ($state) {
            $properties['state'] = $state;
        }

        return \Drupal::entityTypeManager()
            ->getStorage('commerce_payment')
            ->loadByProperties($properties);
    }
}
