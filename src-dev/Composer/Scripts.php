<?php

namespace Cheppers\DrupalExtensionDev\Composer;

use Composer\Script\Event;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class Scripts
{

    public static function prepare(Event $event): int
    {
        $self = new static($event);

        return $self->prepareDoIt();
    }

    public static function drupalInstall(Event $event): int
    {
        $self = new static($event);

        return $self->drupalInstallDoIt();
    }

    /**
     * @var \Composer\Script\Event
     */
    protected $event;

    /**
     * @var string
     */
    protected $binDir = 'bin';

    /**
     * @var string
     */
    protected $projectRoot = 'tests/fixtures/project_01';

    /**
     * @var string
     */
    protected $docroot = 'tests/fixtures/project_01/docroot';

    /**
     * @var string
     */
    protected $sitesDir = 'default';

    /**
     * @var string
     */
    protected $installProfile = 'minimal';

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * @var callable
     */
    protected $processCallbackWrapper;

    protected function __construct(Event $event, ?Filesystem $fs = null)
    {
        $this->event = $event;
        $this->fs = $fs ?: new Filesystem();
        $this->processCallbackWrapper = function (string $type, string $text) {
            $this->processCallback($type, $text);
        };
    }

    protected function prepareDoIt(): int
    {
        $this
            ->prepareDrupal()
            ->prepareBehat();

        return 0;
    }

    protected function drupalInstallDoIt(): int
    {
        $createMenuLinkContent = <<< PHP
\Drupal\menu_link_content\Entity\MenuLinkContent::create([
    'title' => 'Home',
    'link' => ['uri' => 'internal:/'],
    'menu_name' => 'main',
])->save();
PHP;

        $outerSitesDir = $this->getOuterSitesDirPath();
        $drushExecutable = "../../../{$this->binDir}/drush";
        $drushBase = sprintf(
            '%s --no-interaction --ansi',
            escapeshellcmd($drushExecutable)
        );

        $cmdPattern = [];
        $cmdArgs = [];

        $cmdPattern[] = "$drushBase site:install %s";
        $cmdArgs[] = escapeshellarg($this->installProfile);
        $cmdPattern[] = '--sites-subdir=%s';
        $cmdArgs[] = $this->sitesDir;
        $cmdPattern[] = '--db-url=%s';
        $cmdArgs[] = escapeshellarg("sqlite://{$outerSitesDir}/databases/default.sqlite");
        $cmdPattern[] = '--account-name=%s';
        $cmdArgs[] = escapeshellarg('admin');
        $cmdPattern[] = '--account-pass=%s';
        $cmdArgs[] = escapeshellarg('admin');
        $cmdPattern[] = '--existing-config';

        $cmdPattern[] = '&&';
        $cmdPattern[] = "$drushBase php:eval %s";
        $cmdArgs[] = escapeshellarg($createMenuLinkContent);

        $command = vsprintf(implode(' ', $cmdPattern), $cmdArgs);
        $this->event->getIO()->write($command);

        $process = new Process(
            explode(' ', $command),
            $this->projectRoot,
            null,
            null,
            null
        );

        $exitCode = $process->run($this->processCallbackWrapper);
        if ($exitCode !== 0) {
            throw new Exception($process->getErrorOutput(), $exitCode);
        }

        return 0;
    }

    protected function getSitesDirPath(): string
    {
        return Path::join($this->projectRoot, 'docroot', 'sites', $this->sitesDir);
    }

    protected function getOuterSitesDirPath(): string
    {
        return Path::join('..', 'sites', $this->sitesDir);
    }

    protected function prepareDrupal()
    {
        return $this
            ->prepareDrupalSettingsPhp()
            ->prepareDrupalSettingsLocalPhp()
            ->prepareDrupalDatabaseConnection()
            ->prepareDrupalHashSalt()
            ->prepareDrupalPrivateFiles()
            ->prepareDrupalConfigDirectories()
            ->prepareDrupalCssJsAggregation()
            ->prepareDrupalTrustedHostPatterns();
    }

    protected function prepareDrupalSettingsPhp()
    {
        $sitesDirPath = static::getSitesDirPath();
        $fileName = $sitesDirPath . '/settings.php';

        $replacePairs = [];

        $placeholder = <<< 'TEXT'

 * register custom, site-specific service definitions and/or swap out default
 * implementations with custom ones.
 */

/**
 * Database settings:

TEXT;
        $replacePairs[$placeholder] = <<< TEXT

 * register custom, site-specific service definitions and/or swap out default
 * implementations with custom ones.
 */

/**
 * @var string \$app_root
 * @var string \$site_path
 */

/**
 * Database settings:

TEXT;

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

        $this->fileContentReplace($fileName, $replacePairs);

        return $this;
    }

    protected function prepareDrupalSettingsLocalPhp()
    {
        $sitesDirPath = static::getSitesDirPath();
        $srcFilePath = Path::join($this->docroot, 'sites', 'example.settings.local.php');
        $dstFileName = Path::join($sitesDirPath, 'settings.local.php');

        if (!$this->fs->exists($srcFilePath)) {
            $this->event->getIO()->write("Source file is not exists: '$srcFilePath'");

            return $this;
        }

        if ($this->fs->exists($dstFileName)) {
            $this->event->getIO()->write("Destination file already exists: '$dstFileName'");

            return $this;
        }

        $this->fs->copy($srcFilePath, $dstFileName);

        return $this;
    }

    protected function prepareDrupalDatabaseConnection()
    {
        $settingsPhp = Path::join($this->getSitesDirPath(), 'settings.php');
        $databasesDir = Path::join($this->getOuterSitesDirPath(), 'databases');
        $databaseFileDefault = Path::join($databasesDir, 'default.sqlite');
        $databaseFileDefaultSafe = var_export($databaseFileDefault, true);

        $replacePairs = [];

        $placeholder = <<< 'PHP'

$databases = [];

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

        $this->fileContentReplace($settingsPhp, $replacePairs);
        $this->fs->mkdir("{$this->docroot}/$databasesDir");

        return $this;
    }

    protected function prepareDrupalHashSalt()
    {
        $fileName = static::getSitesDirPath() . '/settings.php';
        $outerSitesDir = static::getOuterSitesDirPath();
        $hashSaltFileName = "$outerSitesDir/hash_salt.txt";
        $hashSaltFileNameSafe = var_export($hashSaltFileName, true);

        $this->fs->mkdir("{$this->docroot}/$outerSitesDir");
        $this->fs->dumpFile("{$this->docroot}/$hashSaltFileName", static::generateHashSalt());

        $replacePairs = [];

        $placeholder = <<< 'TEXT'
 * @code
 *   $settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
$settings['hash_salt'] = '';

TEXT;
        $replacePairs[$placeholder] = <<< TEXT
 * @code
 *   \$settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
\$settings['hash_salt'] = file_get_contents($hashSaltFileNameSafe);

TEXT;

        $this->fileContentReplace($fileName, $replacePairs);

        return $this;
    }

    protected function prepareDrupalPrivateFiles()
    {
        $privateFilesDir = $this->getOuterSitesDirPath() . '/private';
        $privateFilesDirSafe = var_export($privateFilesDir, true);

        $replacePairs = [];

        $placeholder = <<< 'PHP'

# $settings['file_private_path'] = '';

PHP;
        $replacePairs[$placeholder] = <<< PHP

\$settings['file_private_path'] = $privateFilesDirSafe;

PHP;

        $this->fs->mkdir("{$this->docroot}/$privateFilesDir");
        $this->fileContentReplace($this->getSitesDirPath() . '/settings.php', $replacePairs);

        return $this;
    }

    protected function prepareDrupalConfigDirectories()
    {
        $prodConfigDir = Path::join($this->getOuterSitesDirPath(), 'config', 'prod');
        $prodConfigDirSafe = var_export($prodConfigDir, true);

        $replacePairs = [];

        $placeholder = <<< 'PHP'

# $settings['config_sync_directory'] = '/directory/outside/webroot';

PHP;

        $replacePairs[$placeholder] = <<< PHP

\$settings['config_sync_directory'] = $prodConfigDirSafe;

PHP;

        $this->fileContentReplace($this->getSitesDirPath() . '/settings.php', $replacePairs);

        return $this;
    }

    protected function prepareDrupalCssJsAggregation()
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

        $this->fileContentReplace($this->getSitesDirPath() . '/settings.local.php', $replacePairs);

        return $this;
    }

    protected function prepareDrupalTrustedHostPatterns()
    {
        $httpHostname = getenv('APP_HTTP_HOSTNAME') ?: 'localhost';
        $httpHostnameSafe = preg_quote($httpHostname);
        $replacePairs = [];

        $placeholder = <<< 'TEXT'

 * will allow the site to run off of all variants of example.com and
 * example.org, with all subdomains included.
 */

/**

TEXT;
        $replacePairs[$placeholder] = <<< TEXT

 * will allow the site to run off of all variants of example.com and
 * example.org, with all subdomains included.
 */
\$settings['trusted_host_patterns'] = [
    '^{$httpHostnameSafe}$',
];

/**

TEXT;

        $this->fileContentReplace($this->getSitesDirPath() . '/settings.php', $replacePairs);

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareProjectComposerJson()
    {
        $content = [
            'name' => 'cheppers/drupal-behat-contexts-tests-project_01',
            'description' => 'cheppers/drupal-behat-contexts-tests-project_01',
            "license" => "proprietary",
            'type' => 'drupal-project',
            'extra' => [
                'installer-types' => [
                    'bower-asset',
                    'npm-asset',
                ],
                'installer-paths' => [
                    'docroot/core' => [
                        'type:drupal-core',
                    ],
                    'docroot/libraries/{$name}' => [
                        'type:drupal-library',
                        'type:bower-asset',
                        'type:npm-asset',
                    ],
                    'docroot/modules/contrib/{$name}' => [
                        'type:drupal-module',
                    ],
                    'docroot/profiles/contrib/{$name}' => [
                        'type:drupal-profile',
                    ],
                    'docroot/themes/contrib/{$name}' => [
                        'type:drupal-theme',
                    ],
                    'drush/Commands/contrib/{$name}' => [
                        'type:drupal-drush',
                    ],
                ],
                'enable-patching' => true,
                'composer-exit-on-patch-failure' => true,
                'patches' => [],
                'drupal-scaffold' => [
                    'excludes' => [
                        'sites/example.settings.local.php',
                        '.csslintrc',
                        '.editorconfig',
                        '.eslintignore',
                        '.eslintrc.json',
                        '.gitattributes',
                        '.ht.router.php',
                        'web.config',
                    ],
                    'initial' => [
                        'sites/default/default.services.yml' => 'sites/default/services.yml',
                        'sites/default/default.settings.php' => 'sites/default/settings.php',
                    ],
                ],
            ],
        ];

        $this->fs->dumpFile(
            "{$this->projectRoot}/composer.json",
            json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $this;
    }

    protected function prepareBehat()
    {
        $this
            ->prepareBehatLocalYml()
            ->prepareBehatRerunCacheDir();

        return $this;
    }

    protected function prepareBehatLocalYml()
    {
        $src = 'tests/behat/behat.local.example.yml';
        $content = file_get_contents($src);
        $this->assertFileContent($src, $content);

        $replacePairs = [
            'localhost:8888' => $this->getHttpHostnamePort(),
            '127.0.0.1:9222' => $this->getChromiumHostnamePort(),
        ];

        $dst = 'tests/behat/behat.local.yml';
        $this->fs->dumpFile($dst, strtr($content, $replacePairs));

        return $this;
    }

    protected function prepareBehatRerunCacheDir()
    {
        $this->fs->mkdir('tmp/behat_rerun_cache');

        return $this;
    }

    protected function generateHashSalt(): string
    {
        return md5(time());
    }

    protected function fileContentReplace(string $fileName, array $replacePairs)
    {
        if (!$this->fs->exists($fileName)) {
            $this->event->getIO()->write("File '$fileName' is missing");

            return $this;
        }

        $content = file_get_contents($fileName);
        $this->assertFileContent($fileName, $content);

        $dirName = dirname($fileName);
        if (!$this->fs->exists($dirName)) {
            $this->fs->mkdir($dirName);
        }

        $mask = umask();
        $this->fs->chmod($dirName, 0777, $mask);
        if ($this->fs->exists($fileName)) {
            $this->fs->chmod($fileName, 0666, $mask);
        }

        $this->fs->dumpFile($fileName, strtr($content, $replacePairs));

        return $this;
    }

    protected function getHttpHostnamePort(): string
    {
        return getenv('APP_HTTP_HOSTNAME_PORT') ?: 'localhost:8888';
    }

    protected function getChromiumHostnamePort(): string
    {
        return getenv('APP_CHROMIUM_HOSTNAME_PORT') ?: '127.0.0.1:9222';
    }

    protected function assertFileContent(string $fileName, $content)
    {
        if ($content === false) {
            throw new Exception(sprintf('Failed to read from file: "%s"', $fileName), 1);
        }

        return $this;
    }

    protected function processCallback(string $type, string $text)
    {
        if ($type === Process::OUT) {
            $this->event->getIO()->write($text, false);

            return;
        }

        $this->event->getIO()->writeError($text, false);
    }
}
