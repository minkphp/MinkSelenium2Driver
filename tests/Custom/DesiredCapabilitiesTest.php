<?php

namespace SilverStripe\MinkSelenium3Driver\Tests\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use SilverStripe\MinkSelenium3Driver\Selenium3Driver;

class DesiredCapabilitiesTest extends TestCase
{
    public function testGetDesiredCapabilities()
    {
        $caps = array(
            'browserName' => 'chrome',
            'version' => '30',
            'platform' => 'ANY',
            'browserVersion' => '30',
            'browser' => 'chrome',
            'name' => 'Selenium3 Mink Driver Test',
            'deviceOrientation' => 'portrait',
            'deviceType' => 'tablet',
            'selenium-version' => '2.45.0'
        );

        $driver = new Selenium3Driver('chrome', $caps);
        $this->assertNotEmpty($driver->getDesiredCapabilities(), 'desiredCapabilities empty');
        $this->assertInternalType('array', $driver->getDesiredCapabilities());
        $this->assertEquals($caps, $driver->getDesiredCapabilities());
    }

    /**
     * @expectedException           \Behat\Mink\Exception\DriverException
     * @expectedExceptionMessage    Unable to set desiredCapabilities, the session has already started
     */
    public function testSetDesiredCapabilities()
    {
        $caps = [
            'browserName' => 'chrome',
            'version' => '30',
            'platform' => 'ANY',
            'browserVersion' => '30',
            'browser' => 'chrome',
            'name' => 'Selenium3 Mink Driver Test',
            'deviceOrientation' => 'portrait',
            'deviceType' => 'tablet',
            'selenium-version' => '3.5.3',
        ];
        $driver = $this->getSession()->getDriver();
        $driver->setDesiredCapabilities($caps);
    }
}
