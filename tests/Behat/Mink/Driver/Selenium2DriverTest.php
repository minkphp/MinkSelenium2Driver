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
        $seleniumHost = $_SERVER['DRIVER_URL'];

        return new Selenium2Driver($browser, null, $seleniumHost);
    }

    /**
     * Data provider with "bad" attributes.
     *
     * @return array
     */
    public function dpGetAttributeWrongBehavior()
    {
        return array(
            array('class', '//input[@id="user-name"]'),
            array('id', '//div/strong[2]'),
            array('style', '//input[@id="user-name"]'),
        );
    }

    /**
     * Test for getAttribute wrong behavior.
     *
     * @dataProvider dpGetAttributeWrongBehavior
     */
    public function testGetAttributeWrongBehavior($attribute, $selector)
    {
        $this->getSession()->visit($this->pathTo('/index.php'));

        $page = $this->getSession()->getPage();
        /**
         * Next two lines shows that getAttribute returns empty string, instead of null.
         */
        $this->assertTrue($page->find('xpath', $selector)->hasAttribute($attribute));
        $this->assertEquals('', $page->find('xpath', $selector)->getAttribute($attribute));
    }

    /**
     * Data provider with "correct" attributes.
     *
     * @return array
     */
    public function dpGetAttributeCorrectBehavior()
    {
        return array(
            array('value', '//div/strong[2]'),
            array('name', '//div/strong[2]'),
            array('data', '//div/strong[2]'),
        );
    }

    /**
     * Test for getAttribute correct behavior.
     *
     * @dataProvider dpGetAttributeCorrectBehavior
     */
    public function testGetAttributeCorrectBehavior($attribute, $selector)
    {
        $this->getSession()->visit($this->pathTo('/index.php'));

        $page = $this->getSession()->getPage();
        /**
         * Next two lines shows that getAttribute for others attributes returns null as expected.
         */
        $this->assertFalse($page->find('xpath', $selector)->hasAttribute($attribute));
        $this->assertNull($page->find('xpath', $selector)->getAttribute($attribute));
    }

    public function testMouseEvents()
    {
    } // Right click and blur are not supported

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
            $this->assertRegExp('\{[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}', $name);
        }
    }

    public function testGetWindowName()
    {
        $session = $this->getSession();

        if (!method_exists($session, 'getWindowName')) {
            $this->markTestSkipped('The "getWindowName method is not available for this session. Skipping this test.');
        }

        $this->assertRegExp('\{[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}', $session->getWindowName());
    }
}
