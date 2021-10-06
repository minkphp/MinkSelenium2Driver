<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Tests\Driver\TestCase;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectException;

class TimeoutTest extends TestCase
{
    use ExpectException;

    public function testInvalidTimeoutSettingThrowsException()
    {
        $this->expectException('\Behat\Mink\Exception\DriverException');
        $this->getSession()->start();

        $this->getSession()->getDriver()->setTimeouts(array('invalid' => 0));
    }

    public function testShortTimeoutDoesNotWaitForElementToAppear()
    {
        $this->getSession()->getDriver()->setTimeouts(array('implicit' => 0));

        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();

        $element = $this->getSession()->getPage()->find('css', '#waitable > div');

        $this->assertNull($element);
    }

    public function testLongTimeoutWaitsForElementToAppear()
    {
        $this->getSession()->getDriver()->setTimeouts(array('implicit' => 5000));

        $this->getSession()->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();
        $element = $this->getSession()->getPage()->find('css', '#waitable > div');

        $this->assertNotNull($element);
    }
}
