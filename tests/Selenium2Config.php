<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\Selenium2Driver;

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

        return new Selenium2Driver($browser, array(
            'browser' => $browser,
            'browserName' => $browser,
            'version' => 'ANY',
            'chromeOptions' => array(
                'args' => array(
                    "no-sandbox",
                    "dbus-stub",
                    "disable-dev-shm-usage",
                    "reduce-security-for-testing",
                    "allow-insecure-localhost",
                    "enable-logging",
                    "disable-setuid-sandbox",
                    "disable-gpu",
                    "disable-web-security",
                    "disk-cache-size=1",
                    "allow-running-insecure-content",
                    "ignore-certificate-errors",
                    "ignore-urlfetcher-cert-requests",
                )
            )
        ), $seleniumHost);
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
            && 'true' === getenv('TRAVIS')
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb.';
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
