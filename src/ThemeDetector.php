<?php

declare(strict_types = 1);

namespace Cheppers\DrupalExtension;

use Behat\Mink\Session;

class ThemeDetector implements ThemeDetectorInterface
{
    /**
     * @var \Behat\Mink\Session
     */
    protected $session;

    /**
     * {@inheritdoc}
     *
     * @todo The current detection method is not bulletproof.
     */
    public function getCurrentThemeName(Session $session): string
    {
        $this->session = $session;

        $themeName = $this->getCurrentThemeNameByFavicon();

        if (!$themeName) {
            $themeName = $this->getCurrentThemeNameByLogo();
        }

        if (!$themeName) {
            $themeName = $this->getCurrentThemeNameByAjaxPageState();
        }

        if (!$themeName) {
            $themeName = 'bartik';
        }

        return $themeName;
    }

    protected function getCurrentThemeNameByFavicon(): string
    {
        $xpathQuery = '/head/link[@rel="shortcut icon"][@href]';

        $page = $this->session->getPage();
        $linkElement = $page->find('xpath', $xpathQuery);

        if (!$linkElement) {
            return '';
        }

        $href = $linkElement->getAttribute('href');
        if ($href === '/core/misc/favicon.ico') {
            return '';
        }

        // @todo Check "files" directory.
        $hrefParts = explode('/', trim($href, '/'));
        array_pop($hrefParts);

        return (string) end($hrefParts);
    }

    protected function getCurrentThemeNameByAjaxPageState(): string
    {
        $js = <<< JS
if (typeof drupalSettings == 'undefined'
  || !drupalSettings.hasOwnProperty('ajaxPageState')
  || !drupalSettings.ajaxPageState.hasOwnProperty('theme')
) {
    return '';
}

return drupalSettings.ajaxPageState.theme;
JS;

        return (string) $this->session->evaluateScript($js);
    }

    protected function getCurrentThemeNameByLogo(): string
    {
        // @todo Drupal is installed in a subdirectory.
        $xpathQuery = '//a[@href="/"]/img[contains(@src, "/logo.svg")]';

        $page = $this->session->getPage();
        $imgElement = $page->find('xpath', $xpathQuery);

        if (!$imgElement) {
            return '';
        }

        $src = $imgElement->getAttribute('src');
        $srcParts = explode('/', trim($src, '/'));
        array_pop($srcParts);

        return (string) end($srcParts);
    }
}
