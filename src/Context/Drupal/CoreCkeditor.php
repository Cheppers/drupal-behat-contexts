<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;

class CoreCkeditor extends Base
{

    /**
     * @When I fill in wysiwyg on field :label with :value
     * @throws \Exception
     */
    public function doFillInWysiwygOnFieldWith(string $label, string $value) {
        $label = mb_strtolower($label);
        $locator = "edit-" . $label . "-0-value";

        $element = $this
            ->getSession()
            ->getPage()
            ->findField($locator);

        Assert::assertNotEmpty(
            $element,
            "Could not find WYSIWYG with locator: '$locator'"
        );

        $fieldId = $element->getAttribute('id');
        Assert::assertNotEmpty(
            $fieldId,
            "Could not find an ID for field with locator: '$locator'"
        );

        $editor = "div.js-form-item-$label-0-value .ck-editor__editable";
        $this
            ->getSession()
            ->executeScript(
                "
                var domEditableElement = document.querySelector(\"$editor\");
                if (domEditableElement.ckeditorInstance) {
                  const editorInstance = domEditableElement.ckeditorInstance;
                  if (editorInstance) {
                    editorInstance.setData(\"$value\");
                  } else {
                    throw new Exception('Could not get the editor instance!');
                  }
                } else {
                  throw new Exception('Could not find the element!');
                }
        ");
    }

}
