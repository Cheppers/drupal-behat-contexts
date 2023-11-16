<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Cheppers\DrupalExtension\Context\Base;
use PHPUnit\Framework\Assert;

class CoreCkeditor extends Base
{

    /**
     * @Then I fill in wysiwyg on field :locator with :value
     */
    public function doFillInWysiwygOnFieldWith(string $locator, string $value)
    {
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

        $this->ckeditorSetData($fieldId, $value);
    }

    /**
     * @return $this
     */
    protected function ckeditorSetData(string $fieldId, string $newValue)
    {
        $fieldIdSafe = addslashes($fieldId);
        $newValueSafe = addslashes($newValue);

        $editor = "div.js-form-item-$fieldIdSafe-0-value .ck-editor__editable";

        $this
            ->getSession()
            ->executeScript("
      var domEditableElement = document.querySelector(\"$editor\");
      if (domEditableElement.CKEditorInstance) {
        const editorInstance = domEditableElement.CKEditorInstance;
        if (editorInstance) {
          editorInstance.setData(\"$newValueSafe\");
        } else {
          throw new Exception('Could not get the editor instance!');
        }
      } else {
        throw new Exception('Could not find the element!');
      }
      ");

        return $this;
    }
}
