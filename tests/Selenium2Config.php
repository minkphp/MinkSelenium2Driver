<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;

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
        $driverOptions = getenv('DRIVER_OPTIONS') ? \json_decode(getenv('DRIVER_OPTIONS'), true) : array();
        $seleniumHost = $_SERVER['DRIVER_URL'];

        if ($browser === 'firefox') {
            $desiredCapabilities = DesiredCapabilities::firefox();
        } else if ($browser === 'chrome') {
            $desiredCapabilities = DesiredCapabilities::chrome();
        } else {
            $desiredCapabilities = new DesiredCapabilities();
        }

        $capabilityMap = array(
            'firefox' => FirefoxDriver::PROFILE,
            'chrome'  => ChromeOptions::CAPABILITY
        );

        if (isset($capabilityMap[$browser])) {
            $capability = $desiredCapabilities->getCapability($capabilityMap[$browser]);
            if ($browser === 'chrome') {
                if (!$capability) {
                    $capability = new ChromeOptions();
                }
                $args = isset($driverOptions['args']) ? $driverOptions['args'] : array();
                $capability->addArguments($args);
                //$capability->addEncodedExtension();
                //$capability->addExtension();
                //$capability->addEncodedExtensions();
                //$capability->addExtensions();
            } else if ($browser === 'firefox') {
                if (!$capability) {
                    $capability = new FirefoxProfile();
                }
                $preferences = isset($driverOptions['preference']) ? $driverOptions['preference'] : array();
                foreach ($preferences as $key => $preference) {
                    $capability->setPreference($key, $preference);
                   // $capability->setRdfFile($key, $preference);
                   // $capability->addExtensionDatas($key, $preference);
                   // $capability->addExtension($key, $preference);
                }
            }

            $desiredCapabilities->setCapability($capabilityMap[$browser], $capability);
        }

        $driver =  new Selenium2Driver($browser, array(), $seleniumHost);
        $driver->setDesiredCapabilities($desiredCapabilities);

        return $driver;
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
