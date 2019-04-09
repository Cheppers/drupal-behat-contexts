<?php

namespace Cheppers\DrupalExtensionDev\Composer;

use Composer\Script\Event;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Scripts
{

    /**
     * @var \Composer\Script\Event
     */
    protected static $event;

    /**
     * @var string
     */
    protected static $binDir = 'bin';

    /**
     * @var string
     */
    protected static $docroot = 'docroot';

    /**
     * @var string
     */
    protected static $sitesDir = 'default';

    /**
     * @var string
     */
    protected static $installProfile = 'standard';

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected static $fs;

    /**
     * @var \Closure
     */
    protected static $processCallbackWrapper;

    public static function prepare(Event $event)
    {
        static::init($event);
        static::prepareDrupal();
        static::prepareBehat();
    }

    public static function drupalInstall(Event $event)
    {
        static::init($event);
        static::drupalInstallDoIt();
    }

    protected static function drupalInstallDoIt()
    {
        $drushExecutable = static::$binDir . '/drush';

        $drushBase = sprintf(
            '%s --no-interaction --ansi --root=%s',
            escapeshellcmd($drushExecutable),
            escapeshellarg(static::$docroot)
        );

        $cmdPattern = [];
        $cmdArgs = [];

        $cmdPattern[] = "$drushBase site:install %s";
        $cmdArgs[] = escapeshellarg(static::$installProfile);


        $cmdPattern[] = '--sites-subdir=%s';
        $cmdArgs[] = escapeshellarg(static::$sitesDir);

        $cmdPattern[] = '--db-url=%s';
        $cmdArgs[] = escapeshellarg('sqlite://../sites/default/databases/default.sqlite');


        $cmdPattern[] = '--account-name=%s --account-pass=%s';
        $cmdArgs[] = escapeshellarg('admin');
        $cmdArgs[] = escapeshellarg('admin');

        $cmdPattern[] = '&&';
        $cmdPattern[] = "$drushBase config:set system.site uuid %s";
        $cmdArgs[] = escapeshellarg('db620b15-2d48-4796-8e81-1aa32ca1df5c');

        $cmdPattern[] = '&&';
        $cmdPattern[] = "$drushBase entity:delete %s";
        $cmdArgs[] = escapeshellarg('shortcut');

        $cmdPattern[] = '&&';
        $cmdPattern[] = "$drushBase config:import";

        $command = vsprintf(implode(' ', $cmdPattern), $cmdArgs);
        static::$event->getIO()->write($command);

        $process = new Process(
            $command,
            null,
            null,
            null,
            null
        );

        $exitCode = $process->run(static::$processCallbackWrapper);
        if ($exitCode !== 0) {
            throw new Exception($process->getErrorOutput(), $exitCode);
        }
    }

    protected static function getSitesDirPath(): string
    {
        return implode('/', [static::$docroot, 'sites', static::$sitesDir]);
    }

    protected static function getRelativePathFromDocrootToProjectRoot(): string
    {
        $numOfDirSeparator = mb_substr_count(static::$docroot, '/');
        $relativePath = str_repeat('../', $numOfDirSeparator + 1);

        return trim($relativePath, '/');
    }

    protected static function getOuterSitesDirPath(): string
    {
        return 'sites/' . static::$sitesDir;
    }

    protected static function init(Event $event)
    {
        static::$event = $event;
        static::$fs = new Filesystem();
        static::$processCallbackWrapper = function (string $type, string $text) {
            static::processCallback($type, $text);
        };
    }

    protected static function prepareDrupal()
    {
        static::prepareDrupalSettingsPhp();
        static::prepareDrupalSettingsLocalPhp();
        static::prepareDrupalDatabaseConnection();
        static::prepareDrupalHashSalt();
        static::prepareDrupalPrivateFiles();
        static::prepareDrupalConfigDirectories();
        static::prepareDrupalCssJsAggregation();
        static::prepareDrupalTrustedHostPatterns();
        static::prepareDrupalInstallProfile();
    }

    protected static function prepareDrupalSettingsPhp()
    {
        $sitesDirPath = static::getSitesDirPath();
        $fileName = "$sitesDirPath/settings.php";

        $replacePairs = [];

        $placeholder = <<< 'PHP'
 * implementations with custom ones.
 */

/**
 * Database settings:
PHP;
        $replacePairs[$placeholder] = <<< 'PHP'
 * implementations with custom ones.
 */

/**
 * @var string $app_root
 * @var string $site_path
 */

/**
 * Database settings:
PHP;

        $placeholder = <<< 'PHP'
#
# if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
#   include $app_root . '/' . $site_path . '/settings.local.php';
# }
PHP;
        $replacePairs[$placeholder] = <<< 'PHP'

if (file_exists("{$app_root}/{$site_path}/settings.local.php")) {
  include "{$app_root}/{$site_path}/settings.local.php";
}
PHP;

        static::fileContentReplace($fileName, $replacePairs);
    }

    protected static function prepareDrupalSettingsLocalPhp()
    {
        $sitesDirPath = static::getSitesDirPath();
        $srcFilePath = static::$docroot . '/sites/example.settings.local.php';
        $dstFileName = "$sitesDirPath/settings.local.php";

        if (!static::$fs->exists($srcFilePath)) {
            static::$event->getIO()->write("Source file is not exists: '$srcFilePath'");

            return;
        }

        if (static::$fs->exists($dstFileName)) {
            static::$event->getIO()->write("Destination file already exists: '$dstFileName'");

            return;
        }

        static::$fs->copy($srcFilePath, $dstFileName);
    }

    protected static function prepareDrupalDatabaseConnection()
    {
        $sitesDirPath = static::getSitesDirPath();
        $fileName = "$sitesDirPath/settings.php";
        $outerSitesDir = static::getOuterSitesDirPath();
        $databasesDir = "$outerSitesDir/databases";
        $databaseFileDefault = "$databasesDir/default.sqlite";
        $backToProjectRoot = static::getRelativePathFromDocrootToProjectRoot();
        $databaseFileDefaultSafe = var_export("$backToProjectRoot/$databaseFileDefault", true);

        $replacePairs = [];

        $placeholder = <<< 'PHP'

$databases = array();

PHP;
        $replacePairs[$placeholder] = <<< PHP

\$databases = [
  'default' => [
    'default' => [
      'driver' => 'sqlite',
      'database' => $databaseFileDefaultSafe,
    ],
  ],
];

PHP;

        static::fileContentReplace($fileName, $replacePairs);
        static::$fs->mkdir($databasesDir);
    }

    protected static function prepareDrupalHashSalt()
    {
        $fileName = static::getSitesDirPath() . '/settings.php';
        $outerSitesDir = static::getOuterSitesDirPath();
        $backToProjectRoot = static::getRelativePathFromDocrootToProjectRoot();
        $hashSaltFileName = "$outerSitesDir/hash_salt.txt";

        static::$fs->mkdir($outerSitesDir);
        static::$fs->dumpFile($hashSaltFileName, static::generateHashSalt());

        $replacePairs = [];

        $hashSaltFileNameFromDocroot = "$backToProjectRoot/$hashSaltFileName";
        $hashSaltFileNameFromDocrootSafe = var_export($hashSaltFileNameFromDocroot, true);
        $placeholder = <<< 'PHP'
 * @endcode
 */
$settings['hash_salt'] = '';

/**
 * Deployment identifier.
PHP;
        $replacePairs[$placeholder] = <<< PHP
 * @endcode
 */
\$settings['hash_salt'] = file_get_contents($hashSaltFileNameFromDocrootSafe);

/**
 * Deployment identifier.
PHP;

        static::fileContentReplace($fileName, $replacePairs);
    }

    protected static function prepareDrupalPrivateFiles()
    {
        $fileName = static::getSitesDirPath() . '/settings.php';
        $outerSitesDir = static::getOuterSitesDirPath();
        $filePrivatePath = "$outerSitesDir/private";
        $backToProjectRoot = static::getRelativePathFromDocrootToProjectRoot();
        $filePrivatePathSafe = var_export("$backToProjectRoot/$filePrivatePath", true);

        $replacePairs = [];

        $placeholder = <<< 'PHP'

# $settings['file_private_path'] = '';

PHP;
        $replacePairs[$placeholder] = <<< PHP

\$settings['file_private_path'] = $filePrivatePathSafe;

PHP;

        static::$fs->mkdir($filePrivatePath);
        static::fileContentReplace($fileName, $replacePairs);
    }

    protected static function prepareDrupalConfigDirectories()
    {
        $fileName = static::getSitesDirPath() . '/settings.php';

        $outerSitesDir = static::getOuterSitesDirPath();
        $backToProjectRoot = static::getRelativePathFromDocrootToProjectRoot();
        $configSyncDir = "$outerSitesDir/config/sync";
        $configSyncDirSafe = var_export("$backToProjectRoot/$configSyncDir", true);

        $replacePairs = [];

        $placeholder = <<< 'PHP'

$config_directories = array();

PHP;

        $replacePairs[$placeholder] = <<< PHP

\$config_directories = [
  'sync' => $configSyncDirSafe,
];

PHP;

        static::fileContentReplace($fileName, $replacePairs);
        static::$fs->mkdir($configSyncDir);
    }

    protected static function prepareDrupalCssJsAggregation()
    {
        $replacePairs = [];

        $placeholder = <<< 'PHP'

$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

PHP;

        $replacePairs[$placeholder] = <<< 'PHP'

$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

PHP;

        static::fileContentReplace(static::getSitesDirPath() . '/settings.local.php', $replacePairs);
    }

    protected static function prepareDrupalTrustedHostPatterns()
    {
        $httpHostname = getenv('APP_HTTP_HOSTNAME') ?: 'localhost';
        $httpHostnameSafe = preg_quote($httpHostname);
        $replacePairs = [];

        $placeholder = <<< 'PHP'
 */

/**
 * The default list of directories that will be ignored by Drupal's file API.
PHP;
        $replacePairs[$placeholder] = <<< PHP
 */

\$settings['trusted_host_patterns'] = [
    '^$httpHostnameSafe$',
];

/**
 * The default list of directories that will be ignored by Drupal's file API.
PHP;

        static::fileContentReplace(static::getSitesDirPath() . '/settings.php', $replacePairs);
    }

    protected static function prepareDrupalInstallProfile()
    {
        $installProfileSafe = var_export(static::$installProfile);
        $replacePairs = [];

        $placeholder = <<< 'PHP'

# $settings['install_profile'] = '';

PHP;
        $replacePairs[$placeholder] = <<< PHP

\$settings['install_profile'] = $installProfileSafe;

PHP;

        static::fileContentReplace(static::getSitesDirPath() . '/settings.php', $replacePairs);
    }

    protected static function prepareBehat()
    {
        static::prepareBehatLocalYml();
        static::prepareBehatRerunCacheDir();
    }

    protected static function prepareBehatLocalYml()
    {
        $src = 'tests/behat/behat.local.example.yml';
        $content = file_get_contents($src);
        static::assertFileContent($src, $content);

        $replacePairs = [
            'localhost:8888' => static::getHttpHostnamePort(),
            '127.0.0.1:9222' => static::getChromiumHostnamePort(),
        ];

        $dst = 'tests/behat/behat.local.yml';
        static::$fs->dumpFile($dst, strtr($content, $replacePairs));
    }

    protected static function prepareBehatRerunCacheDir()
    {
        static::$fs->mkdir('tmp/behat_rerun_cache');
    }

    protected static function generateHashSalt(): string
    {
        return md5(time());
    }

    protected static function fileContentReplace(string $fileName, array $replacePairs): void
    {
        if (!static::$fs->exists($fileName)) {
            static::$event->getIO()->write("File '$fileName' is missing");

            return;
        }

        $content = file_get_contents($fileName);
        static::assertFileContent($fileName, $content);

        $dirName = dirname($fileName);
        if (!static::$fs->exists($dirName)) {
            static::$fs->mkdir($dirName);
        }

        $mask = umask();
        static::$fs->chmod($dirName, 0777, $mask);
        if (static::$fs->exists($fileName)) {
            static::$fs->chmod($fileName, 0666, $mask);
        }

        static::$fs->dumpFile($fileName, strtr($content, $replacePairs));
    }

    protected static function getHttpHostnamePort(): string
    {
        return getenv('APP_HTTP_HOSTNAME_PORT') ?: 'localhost:8888';
    }

    protected static function getChromiumHostnamePort(): string
    {
        return getenv('APP_CHROMIUM_HOSTNAME_PORT') ?: '127.0.0.1:9222';
    }

    protected static function assertFileContent(string $fileName, $content)
    {
        if ($content === false) {
            throw new Exception(sprintf('Failed to read from file: "%s"', $fileName), 1);
        }
    }

    protected static function processCallback(string $type, string $text)
    {
        if ($type === Process::OUT) {
            static::$event->getIO()->write($text, false);

            return;
        }

        static::$event->getIO()->writeError($text, false);
    }
}
