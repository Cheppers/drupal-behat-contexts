<?php

namespace Cheppers\DrupalExtensionDev\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\FeatureScope;
use Behat\Gherkin\Node\FeatureNode;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawMinkContext;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector as CodeCoverageDriverSelector;
use SebastianBergmann\CodeCoverage\Filter as CodeCoverageFilter;
use SebastianBergmann\CodeCoverage\Report\Clover as CloverReporter;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as FacadeReporter;
use Symfony\Component\Filesystem\Filesystem;

class CodeCoverageContext extends RawMinkContext
{

    /**
     * @var null|\SebastianBergmann\CodeCoverage\CodeCoverage
     */
    protected $coverage;

    /**
     * @BeforeFeature
     */
    public static function hookBeforeFeature(BeforeFeatureScope $scope)
    {
        static::cleanReportDirs($scope);
    }

    protected static function getFeatureName(FeatureNode $feature): string
    {
        return preg_replace(
            '@((^features/)|(\.feature$))@',
            '',
            $feature->getFile()
        );
    }

    protected static function cleanReportDirs(FeatureScope $scope)
    {
        (new Filesystem())->remove(static::getReportsDirByReaders($scope));
    }

    protected static function getProjectRoot(): string
    {
        return __DIR__ . '/../..';
    }

    protected static function getReportsDirRoot(): string
    {
        return static::getProjectRoot() . '/reports';
    }

    protected static function getReportsDirTemplate(): string
    {
        return '{root}/{reader}/behat/coverage/{suit}/{feature}';
    }

    /**
     * @param \Behat\Behat\Hook\Scope\FeatureScope|\Behat\Behat\Hook\Scope\ScenarioScope $scope
     */
    protected static function getReportsDirByReaders($scope): array
    {
        return [
            'human' => static::getReportsDirByReader($scope, 'human'),
            'machine' => static::getReportsDirByReader($scope, 'machine'),
        ];
    }

    /**
     * @param \Behat\Behat\Hook\Scope\FeatureScope|\Behat\Behat\Hook\Scope\ScenarioScope $scope
     * @param string $reader
     */
    protected static function getReportsDirByReader($scope, string $reader): string
    {
        $replacePairs = [
            '{root}' => static::getReportsDirRoot(),
            '{reader}' => $reader,
            '{suit}' => $scope->getSuite()->getName(),
            '{feature}' => static::getFeatureName($scope->getFeature()),
        ];

        return strtr(static:: getReportsDirTemplate(), $replacePairs);
    }

    /**
     * @BeforeScenario
     */
    public function hookBeforeScenario(BeforeScenarioScope $scope)
    {
        $this->coverageStart($scope);
    }

    /**
     * @AfterScenario
     */
    public function hookAfterScenario(AfterScenarioScope $scope)
    {
        $this->coverageStop($scope);
    }

    /**
     * @return $this
     */
    protected function coverageStart(BeforeScenarioScope $scope)
    {
        $whitelistedDirs = $this->getWhitelistedDirs();
        if (!$whitelistedDirs) {
            return $this;
        }

        $filter = new CodeCoverageFilter();
        foreach ($this->getWhitelistedDirs() as $whitelistedDir) {
            $filter->includeDirectory($whitelistedDir);
        }

        $this->coverage = new CodeCoverage(
            (new CodeCoverageDriverSelector())->forLineCoverage($filter),
            $filter
        );

        $feature = $scope->getFeature();
        $featureName = static::getFeatureName($feature);

        $this->coverage->start($featureName);

        return $this;
    }

    /**
     * @return $this
     */
    protected function coverageStop(AfterScenarioScope $scope)
    {
        if (!$this->coverage) {
            return $this;
        }

        $this->coverage->stop();

        $scenarioLine = $scope->getScenario()->getLine();

        $reportsDirs = static::getReportsDirByReaders($scope);
        $this
            ->coverageStopClover("{$reportsDirs['machine']}/$scenarioLine.xml")
            ->coverageStopFacade("{$reportsDirs['human']}/$scenarioLine");

        return $this;
    }

    protected function coverageStopClover(string $dstFile) {
        $reporter = new CloverReporter();
        $reporter->process($this->coverage, $dstFile);

        return $this;
    }

    protected function coverageStopFacade(string $dstDir) {
        $reporter = new FacadeReporter();
        $reporter->process($this->coverage, $dstDir);

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getWhitelistedDirs(): array
    {
        $projectRoot = $this->getProjectRoot();

        return [
            "$projectRoot/src",
        ];
    }
}
