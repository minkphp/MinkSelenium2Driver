<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Tests\Driver\TestCase;

class TimeoutTest extends TestCase
{
    /**
     * @after
     */
    protected function resetSessions()
    {
        $session = $this->getSession();

        // Stop the session instead of only resetting it, as timeouts are not reset (they are configuring the session itself)
        if ($session->isStarted()) {
            $session->stop();
        }

        $driver = $session->getDriver();
        \assert($driver instanceof Selenium2Driver);

        // Reset the array of timeouts to avoid impacting other tests
        $driver->setTimeouts(array());

        parent::resetSessions();
    }

    public function testInvalidTimeoutSettingThrowsException()
    {
        $session = $this->getSession();
        $session->start();

        $driver = $session->getDriver();
        \assert($driver instanceof Selenium2Driver);

        $this->expectException('\Behat\Mink\Exception\DriverException');
        $this->expectExceptionMessage('Invalid timeout type: invalid');
        $driver->setTimeouts(array('invalid' => 0));
    }

    public function testShortTimeoutDoesNotWaitForElementToAppear()
    {
        $session = $this->getSession();
        $driver = $session->getDriver();
        \assert($driver instanceof Selenium2Driver);

        $driver->setTimeouts(array('implicit' => 0));

        $session->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();

        $element = $session->getPage()->find('css', '#waitable > div');

        $this->assertNull($element);
    }

    public function testLongTimeoutWaitsForElementToAppear()
    {
        $session = $this->getSession();
        $driver = $session->getDriver();
        \assert($driver instanceof Selenium2Driver);

        $driver->setTimeouts(array('implicit' => 5000));

        $session->visit($this->pathTo('/js_test.html'));
        $this->findById('waitable')->click();
        $element = $session->getPage()->find('css', '#waitable > div');

        $this->assertNotNull($element);
    }

    /**
     * @dataProvider shortPageLoadTimeoutThrowsExceptionDataProvider
     */
    public function testShortPageLoadTimeoutThrowsException(string $timeoutType)
    {
        $session = $this->getSession();
        $driver = $session->getDriver();
        \assert($driver instanceof Selenium2Driver);

        $driver->setTimeouts(array($timeoutType => 1)); // Use 1ms timeout to avoid any successful page load.

        $this->expectException('\Behat\Mink\Exception\DriverException');
        $this->expectExceptionMessage('Page failed to load: ');
        $session->visit('https://www.webpagetest.org/'); // Use external URL, because it loads slower, then local.
    }

    public static function shortPageLoadTimeoutThrowsExceptionDataProvider(): array
    {
        return array(
            'w3c style' => array('pageLoad'),
            'non-w3c style' => array('page load'),
        );
    }
}
