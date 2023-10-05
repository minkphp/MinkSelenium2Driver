<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Tests\Driver\TestCase;

class TimeoutTest extends TestCase
{
    protected $timeouts;

    /**
     * @throws DriverException
     *
     * @return void
     */
    public function setup(): void
    {
        parent::setup();
        $this->getSession()->start();
        $driver = $this->getSession()->getDriver();
        \assert($driver instanceof Selenium2Driver);
        if ($this->getSession()->getDriver()->getWebDriverSession()->isW3C()) {
            $this->timeouts = $this->getSession()->getDriver()->getWebDriverSession()->getTimeouts();
        }
    }

    /**
     * @throws DriverException
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        if ($this->getSession()->getDriver()->getWebDriverSession()->isW3C()) {
            $this->getSession()->getDriver()->setTimeouts($this->timeouts);
        }
        $this->getSession()->stop();
    }

    /**
     * @throws DriverException
     */
    public function testInvalidTimeoutSettingThrowsException()
    {
        $driver = $this->getSession()->getDriver();

        if ($driver->isW3C()) {
            $this->expectException('\WebDriver\Exception\InvalidArgument');
            // The browser will return a 200 for an invalid key, but 400 as
            // expected for an invalid value.
            $driver->setTimeouts(array('script' => -1));
        }
        else {
            $this->expectException('\Behat\Mink\Exception\DriverException');
            $driver->setTimeouts(array('invalid' => 0));
        }
    }

    /**
     * @throws DriverException
     */
    public function testShortTimeoutDoesNotWaitForElementToAppear()
    {
        $session = $this->getSession();
        $driver = $this->getSession()->getDriver();

        $driver->setTimeouts(array('implicit' => 0));

        $session->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();

        $element = $session->getPage()->find('css', '#waitable > div');

        $this->assertNull($element);
    }

    /**
     * @throws DriverException
     */
    public function testLongTimeoutWaitsForElementToAppear()
    {
        $session = $this->getSession();
        $driver = $session->getDriver();

        $driver->setTimeouts(array('implicit' => 5000));

        $session->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();
        $element = $session->getPage()->find('css', '#waitable > div');

        $this->assertNotNull($element);
    }
}
