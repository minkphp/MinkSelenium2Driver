<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Tests\Driver\Selenium2Config;
use Behat\Mink\Tests\Driver\TestCase;

final class SeleniumSupportTest extends TestCase
{
    public function testDriverCannotBeUsedInUnsupportedSelenium(): void
    {
        if (Selenium2Config::getInstance()->isSeleniumVersionSupported()) {
            $this->markTestSkipped('This test applies to unsupported Selenium versions only.');
        }

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('This driver requires Selenium version 3 or lower');

        $this->createDriver()->start();
    }

    public function testThatRightClickingCannotBeUsedInUnsupportedSelenium(): void
    {
        if (Selenium2Config::getInstance()->isRightClickingInSeleniumSupported()) {
            $this->markTestSkipped('This test applies to Selenium 3 only.');
        }

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage(<<<TEXT
Right-clicking via JsonWireProtocol is not possible on Selenium Server 3.x.

Please use the "mink/webdriver-classic-driver" Mink driver or switch to Selenium Server 2.x.
TEXT
        );

        $driver = $this->createDriver();
        $driver->start();
        $driver->rightClick('//');
    }
}
