<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Cheppers\DrupalExtension\Context\Base;
use Drupal\block_content\BlockContentInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

class BlockContentContext extends Base
{
    /**
     * Keep track of block contents so they can be cleaned up.
     *
     * @var array
     */
    protected $blockContents = [];

    /**
     * Creates block content of a given type provided in the form:
     * | info       |
     * | My info    |
     * | ...        |
     *
     * @Given :type block content:
     */
    public function createBlockContents($type, TableNode $blockContentsTable)
    {
        foreach ($blockContentsTable->getHash() as $blockContentHash) {
            $blockContent = (object) $blockContentHash;
            $blockContent->type = $type;
            $this->blockContentCreate($blockContent);
        }
    }

    /**
     * Assert viewing block content given its type and info.
     *
     * @param string $type
     *   Block content type machine name.
     * @param string $info
     *   Block content info.
     *
     * @Given I am visiting the :type block content :info
     * @Given I visit the :type block content :info
     */
    public function iAmViewingTheBlockContent(string $type, string $info)
    {
        $this->visitBlockContentPage('view', $type, $info);
    }

    /**
     * Assert editing block content given its type and title.
     *
     * @param string $type
     *   Block content type machine name.
     * @param string $info
     *   Block content info.
     *
     * @Given I am editing the :type block content :info
     * @Given I edit the :type block content :info
     */
    public function iAmEditingTheBlockContent(string $type, string $info)
    {
        $this->visitBlockContentPage('edit', $type, $info);
    }

    /**
     * Assert deleting block content given its type and info.
     *
     * @param string $type
     *   Block content type machine name.
     * @param string $info
     *   Block content info.
     *
     * @Given I am deleting the :type block content :info
     * @Given I delete the :type block content :info
     */
    public function iAmDeletingTheBlockContent(string $type, string $info)
    {
        $this->visitBlockContentPage('delete', $type, $info);
    }

    /**
     * Provides a common step definition callback for block contents.
     *
     * @param string $op
     *   The operation being performed: 'view', 'edit', 'delete'.
     * @param string $type
     *   The block content type id.
     * @param string $info
     *   The block content info.
     */
    protected function visitBlockContentPage(
        string $op,
        string $type,
        string $info
    ) {
        $bid = $this->getBlockContentIdByInfo($type, $info);
        $path = [
            'view' => "block/$bid",
            'edit' => "block/$bid/edit",
            'delete' => "block/$bid/delete",
        ];
        $this->visitPath($path[$op]);
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
    public function userCanBlockContent(
        string $name,
        string $op,
        string $info
    ) {
        $op = strtr($op, ['edit' => 'update']);

        $block = $this->loadBlockContentByInfo($info);
        $access = $this->blockContentAccess($op, $name, $block);
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
    public function userCanNotBlockContent(
        string $name,
        string $op,
        string $info
    ) {
        $op = strtr($op, ['edit' => 'update']);
        $block = $this->loadBlockContentByInfo($info);
        $access = $this->blockContentAccess($op, $name, $block);
        if ($access) {
            throw new \Exception("{$name} can {$op} '{$info}' but it is not supposed to.");
        }
    }

    /**
     * Assert presence of block content operation links.
     *
     * @param string $link
     *   Link "name" HTML attribute.
     * @param string $operation
     *   The operation.
     * @param string $info
     *   Block content info.
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     *    If no link for operation has been found.
     *
     * @Then I should see the link :link to :operation block content :info
     * @Then I should see a link :link to :operation block content :info
     */
    public function assertBlockContentOperationLink(
        string $link,
        string $operation,
        string $info
    ) {
        if (!$this->getBlockContentOperationLink($link, $operation, $info)) {
            throw new ExpectationException(
                "No '$link' link to '$operation' '$info' has been found.",
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
     * @Then I should not see a link to :operation content :info
     * @Then I should not see the link to :operation content :info
     */
    public function assertNoBlockContentOperationLink(
        string $info,
        string $operation
    ) {
        if ($this->getBlockContentOperationLink('', $operation, $info)) {
            throw new ExpectationException("link to '$operation' '$info' has been found.", $this->getSession());
        }
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
    public function blockContentAccess(
        string $op,
        string $name,
        object $block
    ): bool {
        $account = $this->getCore()->loadUserByName($name);
        return $block->access($op, $account);
    }

    /**
     * Create block content defined in YAML format.
     *
     * @param \Behat\Gherkin\Node\PyStringNode $string
     *   The text in yaml format that represents the content.
     *
     * @Given the following block content:
     */
    public function assertBlockContent(PyStringNode $string)
    {
        $values = $this->sanitizeYaml($string);
        $message = __METHOD__ . ": Required fields 'info' and 'type' not found.";
        Assert::keyExists($values, 'info', $message);
        Assert::keyExists($values, 'type', $message);
        $block = $this->getCore()->entityCreate('block_content', $values);
        $this->blockContents[] = $block;
    }

    /**
     * Helper method to create the block content entity.
     *
     * @param \stdClass $blockContent
     *   The block content values to be created.
     *
     * @return mixed
     *   Returns the block content entity.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    protected function blockContentCreate(\stdClass $blockContent)
    {
        // Throw an exception if the node type is missing or does not exist.
        if (!isset($blockContent->type) || !$blockContent->type) {
            throw new \Exception("Cannot create block content because it is missing the required property 'type'.");
        }

        /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
        $bundle_info = \Drupal::service('entity_type.bundle.info');
        $bundles = $bundle_info->getBundleInfo('block_content');
        if (!in_array($blockContent->type, array_keys($bundles))) {
            throw new \Exception(
                "Cannot create block content because provided block content type '$blockContent->type' does not exist."
            );
        }
        // Remap 'id' to 'info'.
        if (isset($blockContent->id)) {
            $blockContent->info = $blockContent->id;
        }
        $entity = BlockContent::create((array) $blockContent);
        $entity->save();

        $blockContent->id = $entity->id();
        $this->blockContents[] = $blockContent;

        return $blockContent;
    }

    /**
     * Get the edit link for a block.
     *
     * @param string $link
     *   The link name.
     * @param string $operation
     *   The operation.
     * @param string $info
     *   The block info.
     *
     * @return string|null
     *   The link if found.
     *
     * @throws \Exception
     */
    protected function getBlockContentOperationLink(
        string $link,
        string $operation,
        string $info
    ): ?string {
        $block = $this->loadBlockContentByInfo($info);
        $element = $this->getSession()->getPage();
        $locator = ($link ? ['link', sprintf("'%s'", $link)] : ['link', "."]);
        $links = $element->findAll('named', $locator);

        // Loop over all the links and check for the block content edit path.
        foreach ($links as $result) {
            $target = $result->getAttribute('href');
            if (strpos($target, 'block/' . $block->id() . '/' . $operation) !== false) {
                return $result;
            }
        }
        return null;
    }

    /**
     * Loads a block content by name.
     *
     * @param string $info
     *   The info of the block content to load.
     *
     * @return \Drupal\Core\Entity\EntityInterface|null
     *   The loaded block content or null if not found.
     *
     * @throws \Exception
     *   Thrown when no block content with the given info can be loaded.
     */
    public function loadBlockContentByInfo(string $info): ?EntityInterface
    {
        $result = \Drupal::entityQuery('block_content')
            ->condition('info', $info)
            ->range(0, 1)
            ->execute();
        Assert::notEmpty($result);
        $bid = current($result);
        return BlockContent::load($bid);
    }

    /**
     * Remove any created block contents.
     *
     * @AfterScenario
     */
    protected function cleanBlockContents()
    {
        foreach ($this->blockContents as $blockContent) {
            $this->blockContentDelete($blockContent);
        }
        $this->blockContents = [];
    }

    /**
     * Deletes any block content given.
     *
     * @param $blockContent
     *   The block content entity.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     *   Throws exception when the block content does not exist.
     */
    protected function blockContentDelete($blockContent)
    {
        $blockContent = $blockContent instanceof BlockContentInterface
            ? $blockContent
            : BlockContent::load($blockContent->id);
        if ($blockContent instanceof BlockContentInterface) {
            $blockContent->delete();
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
    protected function sanitizeYaml(PyStringNode $node)
    {
        // Sanitize PyString test by removing initial indentation spaces.
        $strings = $node->getStrings();
        if ($strings) {
            preg_match('/^(\s+)/', $strings[0], $matches);
            $indentation_size = isset($matches[1]) ? strlen($matches[1]) : 0;
            foreach ($strings as $key => $string) {
                $strings[$key] = substr($string, $indentation_size);
            }
        }
        $raw = implode("\n", $strings);
        return Yaml::parse($raw);
    }

    /**
     * Collects the block content entity by parameters.
     *
     * @param string $type
     *   The block content type.
     * @param string $info
     *   The block content info.
     *
     * @return mixed|null
     *   Returns the block content if found.
     */
    protected function getBlockContentIdByInfo(
        string $type,
        string $info
    ) {
        $result = $this->findBlockContentByInfo($type, $info);
        Assert::notNull($result, __METHOD__ . ": No Block content with info {$info} found.");
        return $result;
    }

    /**
     * Helper method to find a block content by values.
     *
     * @param string $type
     *   The block content type.
     * @param string $info
     *   The block content info.
     *
     * @return mixed|null
     *   Returns the block content entity if found.
     *
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    protected function findBlockContentByInfo(string $type, string $info)
    {
        $storage = \Drupal::entityTypeManager()->getStorage('block_content');
        $result = $storage
            ->getQuery()
            ->condition('type', $type)
            ->condition('info', $info)
            ->range(0, 1)
            ->accessCheck(false)
            ->execute();
        if (empty($result)) {
            return null;
        }
        return current($result);
    }
}
