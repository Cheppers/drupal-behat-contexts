<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Mink\Element\NodeElement;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Component\Drupal\CoreEntityFormContextTrait;
use Cheppers\DrupalExtension\Component\ScrollToElementContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Exception;
use PHPUnit\Framework\Assert;

class InlineEntityForm extends Base
{
    use CoreContentEntityContextTrait;
    use CoreEntityFormContextTrait;
    use ScrollToElementContextTrait;

    protected function initFinders()
    {
        parent::initFinders();

        $this->finders += [
            'drupal.inline_entity_form.action_buttons' => [
                'selector' => 'xpath',
                'locator' => '//tbody/tr/td[normalize-space(text()) = "{{ entityLabel }}"]/ancestor::tr//button'
            ],
        ];

        return $this;
    }

    /**
     * @When /^I open up the inline entity form of the "(?P<entityLabel>[^"]+)" in the "(?P<fieldName>[^"]+)" field$/
     */
    public function doOpenTheInlineEntityFormByClickOnTheEditButton(string $fieldName, string $entityLabel)
    {
        $actionButtons = $this->getInlineEntityFormActionButtons($fieldName, $entityLabel);
        $editButton = $this->getInlineEntityFormActionButton('edit', $actionButtons);
        if (!$editButton) {
            throw new Exception("@todo Edit button not found");
        }

        $this->scrollToElementByCssLocator('#' . $editButton->getAttribute('id'));
        $editButton->click();
    }

    /**
     * @param string $action
     * @param \Behat\Mink\Element\NodeElement[] $buttons
     */
    protected function getInlineEntityFormActionButton(string $action, array $buttons): ?NodeElement
    {
        $pattern = sprintf('/^edit-variations-entities-\d+-actions-ief-entity-%s$/', $action);
        $editButton = null;
        foreach ($buttons as $button) {
            if ($button->hasAttribute('data-drupal-selector')
                && preg_match($pattern, $button->getAttribute('data-drupal-selector'))
            ) {
                return $button;
            }
        }

        return null;
    }

    /**
     * @return \Behat\Mink\Element\NodeElement[]
     */
    protected function getInlineEntityFormActionButtons(string $fieldName, string $entityLabel): array
    {
        $field = $this->findEntityFormFieldWidgetByFieldName($fieldName);
        Assert::assertNotEmpty(
            $field,
            sprintf('Field not found "%s"', $fieldName)
        );

        $actionButtonsFinder = $this->getFinder(
            'drupal.inline_entity_form.action_buttons',
            [
                '{{ entityLabel }}' => $this->escapeXpathValue($entityLabel),
            ]
        );

        return $field->findAll($actionButtonsFinder['selector'], $actionButtonsFinder['locator']);
    }
}
