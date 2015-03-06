<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Tests\Driver\TestCase;

class desiredCapabilitieTest extends TestCase
{

    public function testGetDesiredCapabilties()
    {
        $driver = $this->getSession()->getDriver();
        $this->assertNotEmpty($driver->getDesiredCapabilities(), 'desiredCapabilities empty');
        $this->assertTrue(gettype($driver->getDesiredCapabilities()) == 'array');
        $this->assertEquals(Selenium2Driver::getDefaultCapabilities(), $driver->getDesiredCapabilities(), 'Expected default capabilities');
    }

    public function testSetDesiredCapabilities()
    {
        $caps = array(
            'browserName'       => 'firefox',
            'version'           => '30',
            'platform'          => 'ANY',
            'browserVersion'    => '30',
            'browser'           => 'firefox',
            'name'              => 'Selenium2 Mink Driver Test',
            'deviceOrientation' => 'portrait',
            'deviceType'        => 'tablet',
            'selenium-version'  => '2.31.0'
        );
        $driver = $this->getSession()->getDriver();
        $this->setExpectedException('\Behat\Mink\Exception\DriverException');
        $driver->setDesiredCapabilities($caps);
    }
}