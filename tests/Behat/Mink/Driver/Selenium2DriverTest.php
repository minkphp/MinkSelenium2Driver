<?php

namespace Tests\Behat\Mink\Driver;

use Behat\Mink\Driver\Selenium2Driver;

/**
 * @group selenium2driver
 */
class Selenium2DriverTest extends JavascriptDriverTest
{
    const WINDOW_NAME_REGEXP = '/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/';

    protected static function getDriver()
    {
        $browser = $_SERVER['WEB_FIXTURES_BROWSER'];
        $seleniumHost = $_SERVER['DRIVER_URL'];

        return new Selenium2Driver($browser, null, $seleniumHost);
    }

    /**
     * @group mouse-events
     */
    public function testFocus()
    {
        $this->markTestSkipped('Not supported currently');
    }

    /**
     * PhantomJS requires waiting a bit for Syn-based events.
     *
     * @param string $action The action being performed
     */
    protected function waitBeforeCheckingMouseEvent($action)
    {
        if ($this->isPhantomJS()) {
            sleep(1);
        }
    }

    public function testIssue178()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/issue178.html'));

        $session->getPage()->findById('source')->setValue('foo');
        $this->assertEquals('foo', $session->getPage()->findById('target')->getText());
    }

    public function testIssue215()
    {
        $session = $this->getSession();
        $session->visit($this->pathTo('/issue215.html'));

        $this->assertContains("foo\nbar", $session->getPage()->findById('textarea')->getValue());
    }

    public function testPatternGetWindowNames()
    {
        $session = $this->getSession();

        if (!method_exists($session, 'getWindowNames')) {
            $this->markTestSkipped('The "getWindowNames method is not available for this session. Skipping this test.');
        }

        $windowNames = $session->getWindowNames();
        $this->assertArrayHasKey(0, $windowNames);

        foreach ($windowNames as $name) {
            $this->assertRegExp(self::WINDOW_NAME_REGEXP, $name);
        }
    }

    public function testGetWindowName()
    {
        $session = $this->getSession();

        if (!method_exists($session, 'getWindowName')) {
            $this->markTestSkipped('The "getWindowName method is not available for this session. Skipping this test.');
        }

        $this->assertRegExp(self::WINDOW_NAME_REGEXP, $session->getWindowName());
    }

    public function testHttpOnlyCookieIsDeleted()
    {
        if ($this->isPhantomJS()) {
            $this->markTestSkipped('This test does not work for PhantomJS currently. See https://github.com/detro/ghostdriver/issues/170');
        }

        parent::testHttpOnlyCookieIsDeleted();
    }

    public function testWindowMaximize()
    {
        if ($this->isPhantomJS()) {
            $this->markTestSkipped('This test does not work for PhantomJS currently. See https://github.com/detro/ghostdriver/issues/287');
        }

        parent::testWindowMaximize();
    }

    /**
     * @expectedException \Behat\Mink\Exception\DriverException
     */
    public function testInvalidTimeoutSettingThrowsException()
    {
        if ($this->isPhantomJS()) {
            $this->markTestSkipped('This test does not work for PhantomJS currently. See https://github.com/detro/ghostdriver/issues/291');
        }

        $this->getSession()->getDriver()->setTimeouts(array('invalid'=>0));
    }

    public function testShortTimeoutDoesNotWaitForElementToAppear()
    {
        $this->getSession()->getDriver()->setTimeouts(array('implicit'=>0));

        $this->getSession()->visit($this->pathTo('/js_test.php'));
        $this->getSession()->getPage()->findById('waitable')->click();

        $element = $this->getSession()->getPage()->find('css', '#waitable > div');

        $this->assertNull($element);
    }

    public function testLongTimeoutWaitsForElementToAppear()
    {
        $this->getSession()->getDriver()->setTimeouts(array('implicit'=>5000));

        $this->getSession()->visit($this->pathTo('/js_test.php'));
        $this->getSession()->getPage()->findById('waitable')->click();
        $element = $this->getSession()->getPage()->find('css', '#waitable > div');

        $this->assertNotNull($element);
    }

    private function isPhantomJS()
    {
        return 'phantomjs' === getenv('WEBDRIVER');
    }
}
