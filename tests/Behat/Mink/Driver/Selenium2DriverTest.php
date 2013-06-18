<?php

namespace Tests\Behat\Mink\Driver;

use Behat\Mink\Driver\Selenium2Driver;

/**
 * @group selenium2driver
 */
class Selenium2DriverTest extends JavascriptDriverTest
{
    protected static function getDriver()
    {
        $browser = $_SERVER['WEB_FIXTURES_BROWSER'];

        return new Selenium2Driver($browser, null, 'http://localhost:4444/wd/hub');
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
        $windowNames = $this->getSession()->getWindowNames();
        $this->assertArrayHasKey(0, $windowNames);

        foreach ($windowNames as $name) {
            $this->assertRegExp('\{[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}', $name);
        }
    }

    public function testGetWindowName()
    {
        $this->assertRegExp('\{[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}', $this->getSession()->getWindowName());
    }

    public function testGetWindowNameCalls()
    {
        $this->getSession()->visit($this->pathTo('/window.php'));
        $session = $this->getSession();
        $page    = $session->getPage();

        $windowName = $this->getSession()->getWindowName();
        $this->assertNotNull($windowName);

        $page->clickLink('Popup #1');
        $page->clickLink('Popup #2');

        $windowNames = $this->getSession()->getWindowNames();
        $this->assertNotNull($windowNames[0]);
        $this->assertNotNull($windowNames[1]);
        $this->assertNotNull($windowNames[2]);
    }
}
