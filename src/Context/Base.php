<?php

namespace Cheppers\DrupalExtension\Context;

use Cheppers\DrupalExtension\Component\Drupal\CoreThemeDetectorContextTrait;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit_Framework_Assert as Assert;

class Base extends RawDrupalContext
{
    use CoreThemeDetectorContextTrait;

    protected $finders = [];

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
        // @todo Parent themes.
        return [
            "{$finderName}__" . $this->getCurrentThemeName(),
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
}
