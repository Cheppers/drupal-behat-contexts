<?php

namespace Cheppers\DrupalExtension\Context;

use Cheppers\DrupalExtension\ThemeDetectorInterface;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

class Base extends RawDrupalContext
{

    /**
     * @var array
     */
    protected $finders = [];

    /**
     * @var null|\Cheppers\DrupalExtension\ThemeDetectorInterface
     */
    protected $themeDetector;

    protected function getThemeDetector(): ThemeDetectorInterface
    {
        return $this->themeDetector ?? $this->getContainer()->get('cheppers.behat.theme_detector');
    }

    public function __construct()
    {
        $this->initFinders();
    }

    /**
     * @return $this
     */
    protected function initFinders()
    {
        return $this;
    }

    protected function isFinderExists(string $id): bool
    {
        return array_key_exists($id, $this->finders) || array_key_exists($id, $this->getDrupalParameter('selectors'));
    }

    protected function getFinder(string $finderName, array $args = []): array
    {
        $drupalSelectors = $this->getDrupalParameter('selectors');
        $finderNameSuggestions = $this->getFinderNameSuggestions($finderName);
        $finder = null;
        foreach ($finderNameSuggestions as $finderName) {
            if (!empty($drupalSelectors[$finderName])) {
                $finder = $drupalSelectors[$finderName];

                break;
            }

            if (!empty($this->finders[$finderName])) {
                $finder = $this->finders[$finderName];

                break;
            }
        }

        Assert::assertNotEmpty(
            $finder,
            sprintf('No such selector configured: "%s"', $finderName)
        );

        $finder = $this->normalizeFinder($finder);
        if ($args) {
            $finder['locator'] = strtr($finder['locator'], $args);
        }

        return $finder;
    }

    /**
     * @return string[]
     */
    protected function getFinderNameSuggestions(string $finderName): array
    {
        $currentThemeName = $this
            ->getThemeDetector()
            ->getCurrentThemeName($this->getSession());

        // @todo Parent themes.
        return [
            "{$finderName}__{$currentThemeName}",
            $finderName,
        ];
    }

    /**
     * @param array|string $finder
     */
    protected function normalizeFinder($finder): array
    {
        if (!is_array($finder)) {
            $matches = [];
            $pattern = '/^(?P<selector>(xpath|css)): /u';
            preg_match($pattern, $finder, $matches);
            if ($matches) {
                return [
                    'selector' => $matches['selector'],
                    'locator' => preg_replace($pattern, '', $finder),
                ];
            }

            $finder = [
                'locator' => $finder,
            ];
        }

        return $finder + ['selector' => 'css'];
    }

    protected function escapeXpathValue(string $value): string
    {
        // @todo Somewhere there is a better solution for this.
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * @param \Behat\Mink\Element\NodeElement[] $nodeElements
     *
     * @return string[]
     */
    protected function nodeElementsToText(array $nodeElements): array
    {
        $return = [];

        foreach ($nodeElements as $key => $nodeElement) {
            $return[$key] = trim($nodeElement->getText());
        }

        return $return;
    }

    /**
     * @param \Behat\Mink\Element\NodeElement[] $nodeElements
     *
     * @return string[]
     */
    protected function nodeElementsToHtml(array $nodeElements): array
    {
        $return = [];
        foreach ($nodeElements as $key => $nodeElement) {
            $return[$key] = $nodeElement->getHtml();
        }

        return $return;
    }
}
