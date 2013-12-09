<?php

namespace Tests\Behat\Mink\Driver;

use Behat\Mink\Driver\Selenium2Driver;

/**
 * @group selenium2driver
 */
class Selenium2DriverTest extends JavascriptDriverTest
{
    const WINDOW_NAME_REGEXP = '/\{[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}/';

    protected static function getDriver()
    {
        $browser = $_SERVER['WEB_FIXTURES_BROWSER'];
        $seleniumHost = $_SERVER['DRIVER_URL'];

        return new Selenium2Driver($browser, null, $seleniumHost);
    }

    public function testMouseEvents() {} // Right click and blur are not supported

    public function testOtherMouseEvents()
    {
        $this->getSession()->visit($this->pathTo('/js_test.php'));

        $clicker = $this->getSession()->getPage()->find('css', '.elements div#clicker');

        $this->assertEquals('not clicked', $clicker->getText());

        $clicker->click();
        $this->assertEquals('single clicked', $clicker->getText());

        $clicker->doubleClick();
        $this->assertEquals('double clicked', $clicker->getText());

        $clicker->mouseOver();
        $this->assertEquals('mouse overed', $clicker->getText());
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

    public function testGetWindowNames()
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
    
    public function testValidTimeoutSettingsDoNotCauseError()
    {
       $session = $this->getSession()->getDriver()
           ->setTimeouts(array('script'=>1234, 'implicit'=>5678));
    }
    
    /**
     * @expectedException Behat\Mink\Exception\DriverException
     */
    public function testInvalidTimeoutSettingsThrowException()
    {
       $session = $this->getSession()->getDriver()
           ->setTimeouts(array('foo'=>1234));
    }
}
