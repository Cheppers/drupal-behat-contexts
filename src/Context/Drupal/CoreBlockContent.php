<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

class CoreBlockContent extends Base
{

    use CoreContentEntityContextTrait;

    /**
     * Creates block content of a given type provided in the form:
     * | info           |
     * | My block label |
     * | ...            |
     *
     * @param string $type
     *   The block content type.
     * @param \Behat\Gherkin\Node\TableNode $blockContentsTable
     *   The block content field values.
     *
     * @Given :type block content:
     */
    public function doCreateBlockContents(string $type, TableNode $blockContentsTable)
    {
        foreach ($blockContentsTable->getHash() as $blockContent) {
            $blockContent['type'] = $type;
            $this->createContentEntity('block_content', $blockContent);
        }
    }

    /**
     * Create block content defined in YAML format.
     *
     * @param \Behat\Gherkin\Node\PyStringNode $string
     *   The text in yaml format that represents the content.
     *
     * @Given the following block content:
     */
    public function doCreateBlockContent(PyStringNode $string)
    {
        $values = $this->doSanitizeYaml($string);
        $message = __METHOD__ . ": Required fields 'info' and 'type' not found.";
        Assert::keyExists($values, 'info', $message);
        Assert::keyExists($values, 'type', $message);
        $this->getCore()->entityCreate('block_content', $values);
    }

    /**
     * Assert editing block content given its type and title.
     *
     * @param string $info
     *   Block content info.
     *
     * @Given I am editing the block content :info
     * @Given I edit the block content :info
     */
    public function doEditTheBlockContent(string $info)
    {
        $url = $this->getContentEntityUrlByLabel(
            'block_content',
            $info,
            'edit-form'
        );
        $this->visitPath($url);
    }

    /**
     * Assert deleting block content given its type and info.
     *
     * @param string $info
     *   Block content info.
     *
     * @Given I am deleting the block content :info
     * @Given I delete the block content :info
     */
    public function doDeleteTheBlockContent(string $info)
    {

        $url = $this->getContentEntityUrlByLabel(
            'block_content',
            $info,
            'delete-form'
        );
        $this->visitPath($url);
    }

    /**
     * Assert that given user can perform given operation on block content.
     *
     * @param string $name
     *   User name.
     * @param string $op
     *   Operation: view, edit or delete.
     * @param string $info
     *   Block content info.
     *
     * @throws \Exception
     *   If user cannot perform given operation on given block content.
     *
     * @Then :name can :op block content :info
     * @Then :name can :op :info block content
     */
    public function doUserCanBlockContent(
        string $name,
        string $op,
        string $info
    ) {
        $op = strtr($op, ['edit' => 'update']);
        $block = $this->getContentEntityByLabel('block_content', $info);
        $access = $this->assertBlockContentAccess($op, $name, $block);
        if (!$access) {
            throw new \Exception("{$name} cannot {$op} '{$info}' but it is supposed to.");
        }
    }

    /**
     * Assert that given user cannot perform given operation on block content.
     *
     * @param string $name
     *   User name.
     * @param string $op
     *   Operation: view, edit or delete.
     * @param string $info
     *   Block content info.
     *
     * @throws \Exception
     *   If user can perform given operation on given block content.
     *
     * @Then :name can not :op block content :info
     * @Then :name cannot :op block content :info
     * @Then :name cannot :op :info block content
     */
    public function doUserCanNotBlockContent(
        string $name,
        string $op,
        string $info
    ) {
        $op = strtr($op, ['edit' => 'update']);
        $block = $this->getContentEntityByLabel('block_content', $info);
        $access = $this->assertBlockContentAccess($op, $name, $block);
        if ($access) {
            throw new \Exception("{$name} can {$op} '{$info}' but it is not supposed to.");
        }
    }

    /**
     * Assert presence of block content operation links.
     *
     * @param string $operation
     *   The operation.
     * @param string $info
     *   Block content info.
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     *    If no link for operation has been found.
     *
     * @Then I should see the :operation link to block content :info
     * @Then I should see a :operation link to block content :info
     */
    public function doShouldSeeBlockContentOperationLink(
        string $operation,
        string $info
    ) {
        $block = $this->getContentEntityByLabel('block_content', $info);
        $url = $this->getContentEntityOperationLink($block, $op);
        if (!$url) {
            throw new ExpectationException(
                "No link to '$operation' '$info' has been found.",
                $this->getSession()
            );
        }
    }

    /**
     * Assert absence of block content operation links.
     *
     * @param string $info
     *   Block content info.
     * @param string $operation
     *   The operation.
     *
     * @throws \Behat\Mink\Exception\ExpectationException|\Exception
     *    If link for operation has been found.
     *
     * @Then I should not see the :operation link to block content :info
     * @Then I should not see a :operation link to block content :info
     */
    public function doShouldNotSeeBlockContentOperationLink(
        string $info,
        string $operation
    ) {
        $block = $this->getContentEntityByLabel('block_content', $info);
        $url = $this->getContentEntityOperationLink($block, $op);
        if ($url) {
            throw new ExpectationException("link to '$operation' '$info' has been found.", $this->getSession());
        }
    }

    /**
     * Sanitize then parse a string.
     *
     * @param \Behat\Gherkin\Node\PyStringNode $node
     *   The string to be sanitized.
     *
     * @return mixed
     *   The YAML converted to a PHP value.
     */
    public function doSanitizeYaml(PyStringNode $node)
    {
        // Sanitize PyString test by removing initial indentation spaces.
        $strings = $node->getStrings();
        if ($strings) {
            preg_match('/^(\s+)/', $strings[0], $matches);
            $indentationSize = isset($matches[1]) ? strlen($matches[1]) : 0;
            foreach ($strings as $key => $string) {
                $strings[$key] = substr($string, $indentationSize);
            }
        }
        $raw = implode("\n", $strings);
        return Yaml::parse($raw);
    }

    /**
     * Check whereas a user can perform and operation on a given block content.
     *
     * @param string $op
     *   Operation: view, update or delete.
     * @param string $name
     *   Username.
     * @param object $block
     *   Block content object.
     *
     * @return bool
     *   TRUE if user can perform operation, FALSE otherwise.
     */
    public function assertBlockContentAccess(
        string $op,
        string $name,
        object $block
    ): bool {
        $account = $this->getCore()->loadUserByName($name);
        return $block->access($op, $account);
    }
}
