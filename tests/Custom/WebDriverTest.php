<?php

namespace SilverStripe\MinkSelenium3Driver\Tests\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use SilverStripe\MinkSelenium3Driver\Selenium3Driver;

class WebDriverTest extends TestCase
{
    public function testGetWebDriverSessionId()
    {
        /** @var Selenium3Driver $driver */
        $driver = $this->getSession()->getDriver();
        $this->assertNotEmpty($driver->getWebDriverSessionId(), 'Started session has an ID');

        $driver = new Selenium3Driver();
        $this->assertNull($driver->getWebDriverSessionId(), 'Not started session don\'t have an ID');
    }
}
