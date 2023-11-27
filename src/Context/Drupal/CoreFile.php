<?php

namespace Cheppers\DrupalExtension\Context\Drupal;

use Behat\Gherkin\Node\TableNode;
use Cheppers\DrupalExtension\Component\Drupal\CoreContentEntityContextTrait;
use Cheppers\DrupalExtension\Context\Base;
use Drupal;
use Drupal\file\FileInterface;
use PHPUnit\Framework\Assert;
use Symfony\Component\Filesystem\Path;

class CoreFile extends Base
{
    use CoreContentEntityContextTrait;

    /**
     * @Given /^files:$/
     */
    public function doCreateFiles(TableNode $tableNode)
    {
        $fileStorage = Drupal::entityTypeManager()->getStorage('file');
        $baseDir = $this->getMinkParameter('files_path');
        foreach ($tableNode->getColumnsHash() as $row) {
            $srcPath = Path::join($baseDir, $row['source']);
            /** @var \Drupal\file\FileInterface $file */
            $file = $fileStorage->create([
                'filename' => pathinfo($srcPath, PATHINFO_BASENAME),
                'uri' => $srcPath,
            ]);

            if (isset($row['uid'])) {
                $owner = $this->getContentEntityByLabel('user', $row['uid']);
                $file->setOwnerId($owner->id());
            }

            $dstDirName = Path::getDirectory($row['uri']);
            /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
            $fileSystem = \Drupal::service('file_system');
            $fileSystem->prepareDirectory($dstDirName, $fileSystem::CREATE_DIRECTORY);
            $file = $fileSystem->copy($file->getFileUri(), $row['uri'], $fileSystem::EXISTS_REPLACE);
            Assert::assertInstanceOf(
                FileInterface::class,
                $file,
                "Copy file from '{$row['source']}' to '{$row['uri']}'"
            );
        }
    }
}
