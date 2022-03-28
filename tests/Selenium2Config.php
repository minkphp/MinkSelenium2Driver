<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Tests\Driver\Basic\BasicAuthTest;

class Selenium2Config extends AbstractConfig
{
    public static function getInstance()
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver()
    {
        $browser = getenv('WEB_FIXTURES_BROWSER') ?: 'firefox';
        $seleniumHost = $_SERVER['DRIVER_URL'];

        return new Selenium2Driver($browser, null, $seleniumHost);
    }

    /**
     * {@inheritdoc}
     */
    public function skipMessage($testCase, $test)
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

        if (BasicAuthTest::class === $testCase && 'testBasicAuthInUrl' === $test) {
            return 'Basic auth setup is not supported.';
        }

        return parent::skipMessage($testCase, $test);
    }

    /**
     * {@inheritdoc}
     */
    protected function supportsCss()
    {
        return true;
    }
}
