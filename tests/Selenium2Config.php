<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Tests\Driver\Basic\BasicAuthTest;
use Behat\Mink\Tests\Driver\Basic\HeaderTest;
use Behat\Mink\Tests\Driver\Basic\StatusCodeTest;
use Behat\Mink\Tests\Driver\Css\HoverTest;
use Behat\Mink\Tests\Driver\Js\EventsTest;
use Behat\Mink\Tests\Driver\Js\JavascriptTest;

class Selenium2Config extends AbstractConfig
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function createDriver(): DriverInterface
    {
        $browser = getenv('WEB_FIXTURES_BROWSER') ?: 'firefox';
        $seleniumHost = $_SERVER['DRIVER_URL'];

        return new Selenium2Driver($browser, null, $seleniumHost);
    }

    public function mapRemoteFilePath($file): string
    {
        if (!isset($_SERVER['TEST_MACHINE_BASE_PATH'])) {
            $webFixturesPath = dirname(__DIR__) . '/vendor/mink/driver-testsuite/web-fixtures';
            $_SERVER['TEST_MACHINE_BASE_PATH'] = realpath($webFixturesPath) . DIRECTORY_SEPARATOR;
        }

        return parent::mapRemoteFilePath($file);
    }

    public function skipMessage($testCase, $test): ?string
    {
        if (
            'Behat\Mink\Tests\Driver\Form\Html5Test' === $testCase
            && 'testHtml5Types' === $test
        ) {
            return 'WebDriver does not support setting value in color inputs. See https://code.google.com/p/selenium/issues/detail?id=7650';
        }

        if (
            'Behat\Mink\Tests\Driver\Js\WindowTest' === $testCase
            && (0 === strpos($test, 'testWindowMaximize'))
            && 'true' === getenv('GITHUB_ACTIONS')
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb.';
        }

        if (BasicAuthTest::class === $testCase) {
            return 'Basic auth is not supported.';
        }

        if (HeaderTest::class === $testCase) {
            return 'Headers are not supported.';
        }

        if (StatusCodeTest::class === $testCase) {
            return 'Checking status code is not supported.';
        }

        if (array(JavascriptTest::class, 'testDragDropOntoHiddenItself') === array($testCase, $test)) {
            $seleniumVersion = $_SERVER['SELENIUM_VERSION'] ?? null;
            $browser = $_SERVER['WEB_FIXTURES_BROWSER'] ?? null;

            if ($seleniumVersion && version_compare($seleniumVersion, '3.0.0', '<') && $browser === 'firefox') {
                return 'The Firefox browser compatible with Selenium Server 2.x doesn\'t fully implement drag-n-drop support.';
            }
        }

        if (array(HoverTest::class, 'testRightClickHover') === array($testCase, $test)
            || array(EventsTest::class, 'testRightClick') === array($testCase, $test)
        ) {
            list($majorSeleniumServerVersion) = explode('.', $_SERVER['SELENIUM_VERSION'] ?? '0.0.0');

            if ((int)$majorSeleniumServerVersion === 3) {
                return 'The Selenium Server 3.x doesn\'t support right-clicking via JsonWireProtocol. See https://github.com/SeleniumHQ/selenium/commit/085ceed1f55fbaaa1d419b19c73264415c394905.';
            }
        }

        return parent::skipMessage($testCase, $test);
    }

    protected function supportsCss(): bool
    {
        return true;
    }
}
