<?php

namespace Behat\Mink\Tests\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class Selenium2Config extends AbstractConfig
{
    /**
     * @var Selenium2Driver
     */
    private $driver;

    public static function getInstance()
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver()
    {
        $browser = getenv('BROWSER_NAME') ?: 'firefox';
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
                $capability = $this->buildChromeOptions($capability, $driverOptions);
            } else if ($browser === 'firefox') {
                $capability = $this->buildFirefoxProfile($capability, $driverOptions);
            }

            $desiredCapabilities->setCapability($capabilityMap[$browser], $capability);
        }

        $driver =  new Selenium2Driver($browser, array(), $seleniumHost);
        $driver->setDesiredCapabilities($desiredCapabilities);

        return $this->driver = $driver;
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

        $desiredCapabilities = $this->driver->getDesiredCapabilities();
        $chromeOptions = $desiredCapabilities->getCapability(ChromeOptions::CAPABILITY);

        $headless = $desiredCapabilities->getBrowserName() === 'chrome'
            && $chromeOptions instanceof ChromeOptions
            && in_array('headless', $chromeOptions->toArray()['args'], true);

        if (
            'Behat\Mink\Tests\Driver\Js\WindowTest' === $testCase
            && (0 === strpos($test, 'testWindowMaximize'))
            && ('true' === getenv('TRAVIS') || $headless)
        ) {
            return 'Maximizing the window does not work when running the browser in Xvfb/Headless.';
        }

        if (
            PHP_OS === 'Darwin'
            && 'Behat\Mink\Tests\Driver\Js\EventsTest' === $testCase
            && 0 === strpos($test, 'testKeyboardEvents')
        ) {
            // https://bugs.chromium.org/p/chromium/issues/detail?id=13891#c16
            // Control + <char> will not trigger keypress
            // Option + <char> will output different results "special char" ©
            return 'MacOS does not behave same as Windows or Linux';
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

    /**
     * @param ChromeOptions|null $capability
     * @param array              $driverOptions
     *
     * @return ChromeOptions
     */
    private function buildChromeOptions($capability, array $driverOptions)
    {
        if (!$capability) {
            $capability = new ChromeOptions();
        }
        $args = isset($driverOptions['args']) ? $driverOptions['args'] : [];
        $capability->addArguments($args);
        return $capability;

        //$capability->addEncodedExtension();
        //$capability->addExtension();
        //$capability->addEncodedExtensions();
        //$capability->addExtensions();
    }

    /**
     * @param FirefoxProfile|null $capability
     * @param array               $driverOptions
     *
     * @return FirefoxProfile
     */
    private function buildFirefoxProfile($capability, array $driverOptions)
    {
        if (!$capability) {
            $capability = new FirefoxProfile();
        }

        $preferences = isset($driverOptions['preference']) ? $driverOptions['preference'] : [];
        foreach ($preferences as $key => $preference) {
            $capability->setPreference($key, $preference);
            // $capability->setRdfFile($key, $preference);
            // $capability->addExtensionDatas($key, $preference);
            // $capability->addExtension($key, $preference);
        }
        return $capability;
    }
}
