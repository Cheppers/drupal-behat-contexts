<?php

namespace Cheppers\DrupalExtension;

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
    public function getCurrentThemeName(\Behat\Mink\Session $session): string
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

        $hrefParts = explode('/', trim($href, '/'));
        array_pop($hrefParts);

        return (string) end($hrefParts);
    }

    protected function getCurrentThemeNameByAjaxPageState(): string
    {
        $js = <<< JS
if (typeof drupalSettings !== 'undefined' && drupalSettings.hasOwnProperty('ajaxPageState')) {
    return drupalSettings.ajaxPageState.theme;
}

return '';
JS;

        return (string) $this->session->evaluateScript($js);
    }

    protected function getCurrentThemeNameByLogo(): string
    {
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
