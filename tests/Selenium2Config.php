<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Tests\Driver\Basic\BasicAuthTest;
use Behat\Mink\Tests\Driver\Basic\HeaderTest;
use Behat\Mink\Tests\Driver\Basic\StatusCodeTest;

class Selenium2Config extends AbstractConfig
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function createDriver(): DriverInterface
    {
        $browser = getenv('WEB_FIXTURES_BROWSER') ?: 'firefox';
        $seleniumHost = getenv('DRIVER_URL');

        return new Selenium2Driver($browser, null, $seleniumHost);
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

        return parent::skipMessage($testCase, $test);
    }

    protected function supportsCss(): bool
    {
        return true;
    }
}
