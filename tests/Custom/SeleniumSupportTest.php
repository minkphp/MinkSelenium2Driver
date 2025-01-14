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
}
