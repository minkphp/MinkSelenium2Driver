<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Tests\Driver\TestCase;
use WebDriver\WebDriver;

class WebDriverTest extends TestCase
{
    public function testGetWebDriverSessionId()
    {
        $session = $this->getSession();
        $session->start();
        /** @var Selenium2Driver $driver */
        $driver = $session->getDriver();
        $this->assertNotEmpty($driver->getWebDriverSessionId(), 'Started session has an ID');

        $driver = new Selenium2Driver();
        $this->assertNull($driver->getWebDriverSessionId(), 'Not started session don\'t have an ID');
    }

    public function testUnsupportedStatusResponseHandling(): void
    {
        $mockWebDriver = $this->createMock(WebDriver::class);
        $mockWebDriver->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('status'))
            ->willThrowException(new \RuntimeException('some internal error'));

        $driver = new Selenium2Driver();
        $driver->setWebDriver($mockWebDriver);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Selenium Server version could not be detected: some internal error');

        $driver->start();
    }
}
