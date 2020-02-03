<?php

declare(strict_types = 1);

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;
use Cheppers\DrupalExtension\Context\Assert as A;

class CoreMessage extends Base
{
    /**
     * @var string[]
     */
    protected $messageTypes = [];

    /**
     * {@inheritdoc}
     */
    protected function initFinders()
    {
        parent::initFinders();

        $prefix = '*[data-drupal-messages] > .messages.messages';
        $suffix = '.messages__list > .messages__item';

        $this->finders += [
            'drupal.core.message.single' => [
                'selector' => 'css',
                'locator' => $prefix . '--{{ messageType }} > *[role="alert"]',
            ],
            'drupal.core.message.multiple' => [
                'selector' => 'css',
                'locator' => $prefix . '--{{ messageType }} > *[role="alert"] > ' . $suffix,
            ],
            'drupal.core.message.single.status' => [
                'selector' => 'css',
                'locator' => $prefix . '--status > *[role="alert"]',
            ],
            'drupal.core.message.multiple.status' => [
                'selector' => 'css',
                'locator' => $prefix . '--status > *[role="alert"] > ' . $suffix,
            ],
            'drupal.core.message.single.warning' => [
                'selector' => 'css',
                'locator' => $prefix . '--warning > *[role="alert"]',
            ],
            'drupal.core.message.multiple.warning' => [
                'selector' => 'css',
                'locator' => $prefix . '--warning > *[role="alert"] > ' . $suffix,
            ],
            'drupal.core.message.single.error' => [
                'selector' => 'css',
                'locator' => $prefix . '--error > *[role="alert"]',
            ],
            'drupal.core.message.multiple.error' => [
                'selector' => 'css',
                'locator' => $prefix . '--error > *[role="alert"] > ' . $suffix,
            ],
        ];

        return $this;
    }

    /**
     * @Then I should see :amount :messageType message(s)
     */
    public function assertMessagesAmountByType(string $messageType, $amount)
    {
        Assert::assertSame(intval($amount), count($this->getActualMessagesByType($messageType)));
    }

    /**
     * @Then I should see :amount message(s)
     */
    public function assertMessagesAmountTotal(string $amount)
    {
        $numOfMessages = 0;
        foreach ($this->getActualMessagesGroupByType() as $messages) {
            $numOfMessages += count($messages);
        }

        Assert::assertSame(intval($amount), $numOfMessages);
    }

    /**
     * @Then I should see the following :messageType message :message
     */
    public function assertMessage(string $messageType, string $message)
    {
        Assert::assertContains(
            $message,
            $this->nodeElementsToText($this->getActualMessagesByType($messageType))
        );
    }

    /**
     * @Then I should not see the following :messageType message :message
     */
    public function assertMessageNot(string $messageType, string $message)
    {
        Assert::assertNotContains(
            $message,
            $this->nodeElementsToText($this->getActualMessagesByType($messageType))
        );
    }

    /**
     * @Then I should see a(n) :messageType message like this :format
     */
    public function assertMessageMatchesFormat(string $messageType, string $format)
    {
        A::assertOneOfStringsMatchesFormat(
            $format,
            $this->nodeElementsToText($this->getActualMessagesByType($messageType))
        );
    }

    /**
     * @Then I should not see a(n) :messageType message like this :format
     */
    public function assertMessageNotMatchesFormat(string $messageType, string $format)
    {
        A::assertNonOfStringsMatchesFormat(
            $format,
            $this->nodeElementsToText($this->getActualMessagesByType($messageType))
        );
    }

    /**
     * @Then I should see only the following :messageType message(s):
     */
    public function assertMessagesByTypeSame(string $messageType, TableNode $tableNode)
    {
        Assert::assertSame(
            $tableNode->getColumn(0),
            $this->nodeElementsToText($this->getActualMessagesByType($messageType))
        );

        return $this;
    }

    /**
     * @Then Is should not see any messages
     */
    public function assertMessagesEmpty()
    {
        $messagesByType = $this->getActualMessagesGroupByType();
        foreach ($messagesByType as $messageType => $messages) {
            Assert::assertEmpty(
                $messages,
                "There is no any '$messageType' message"
            );
        }
    }

    /**
     * @Then Is should not see any :messageType messages
     */
    public function assertMessagesEmptyByType(string $messageType)
    {
        Assert::assertEmpty(
            $this->getActualMessagesByType($messageType),
            "There is no any '$messageType' message"
        );
    }

    /**
     * @return \Behat\Mink\Element\NodeElement[]
     */
    protected function getActualMessagesByType(string $type): array
    {
        $finder = $this->isFinderExists("drupal.core.message.multiple.$type") ?
            $this->getFinder("drupal.core.message.multiple.$type")
            : $this->getFinder('drupal.core.message.multiple', ['{{ messageType }}' => $type]);

        $items = $this
            ->getSession()
            ->getPage()
            ->findAll($finder['selector'], $finder['locator']);

        if (!$items) {
            $finder = $this->isFinderExists("drupal.core.message.single.$type") ?
                $this->getFinder("drupal.core.message.single.$type")
                : $this->getFinder('drupal.core.message.single', ['{{ messageType }}' => $type]);

            $items = $this
                ->getSession()
                ->getPage()
                ->findAll($finder['selector'], $finder['locator']);
        }

        return $items;
    }

    protected function getActualMessagesGroupByType(array $types = []): array
    {
        $messageTypes = array_unique(array_merge($this->getMessageTypes(), $types));
        $messages = [];
        foreach ($messageTypes as $messageType) {
            $messages[$messageType] = $this->getActualMessagesByType($messageType);
        }

        return $messages;
    }

    /**
     * @return string[]
     */
    protected function getMessageTypes(): array
    {
        if (!$this->messageTypes) {
            $finderIds = array_unique(array_merge(
                array_keys($this->getDrupalParameter('selectors')),
                array_keys($this->finders)
            ));

            foreach ($finderIds as $finderId) {
                $matches = [];
                if (preg_match('/^drupal\.core\.message\.single\.(?P<type>.+)$/', $finderId, $matches)) {
                    $this->messageTypes[] = $matches['type'];
                }
            }
        }

        return $this->messageTypes;
    }
}
