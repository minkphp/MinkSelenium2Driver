<?php

namespace Behat\Mink\Tests\Driver\Custom;

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

        // Reset the array of timeouts to avoid impacting other tests
        $session->getDriver()->setTimeouts(array());

        parent::resetSessions();
    }

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
