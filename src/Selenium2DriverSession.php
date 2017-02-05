<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver;

use Behat\Mink\Exception\DriverException;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class Selenium2DriverSession
{
    /**
     * @var RemoteWebDriver
     */
    protected $webDriver;

    /**
     * @param RemoteWebDriver $webDriver
     */
    public function __construct(RemoteWebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
    }

    /**
     * @param  array $params
     * @return mixed
     */
    public function execute($params)
    {
        return $this->webDriver->execute(DriverCommand::EXECUTE_SCRIPT, $params);
    }

    /**
     * @param  array $params
     * @return mixed
     */
    public function execute_async($params)
    {
        return $this->webDriver->execute(DriverCommand::EXECUTE_ASYNC_SCRIPT, $params);
    }

    public function close()
    {
        $this->webDriver->close();
    }

    public function deleteAllCookies()
    {
        $this->webDriver->manage()->deleteAllCookies();
    }

    public function open($url)
    {
        $this->webDriver->get($url);
    }

    /**
     * @return string
     */
    public function url()
    {
        return $this->webDriver->getCurrentURL();
    }

    public function refresh()
    {
        return $this->webDriver->navigate()->refresh();
    }

    public function forward()
    {
        return $this->webDriver->navigate()->forward();
    }

    public function back()
    {
        return $this->webDriver->navigate()->back();
    }

    public function deleteCookie($name)
    {
        return $this->webDriver->manage()->deleteCookieNamed($name);
    }

    public function setCookie($cookie)
    {
        $this->webDriver->manage()->addCookie($cookie);
    }

    /**
     * @return array
     */
    public function getAllCookies()
    {
        return $this->webDriver->manage()->getCookies();
    }

    /**
     * @return string
     */
    public function source()
    {
        return $this->webDriver->getPageSource();
    }

    /**
     * @return string
     */
    public function screenshot()
    {
        return $this->webDriver->takeScreenshot();
    }

    /**
     * @param string $name
     */
    public function focusWindow($name)
    {
        $this->webDriver->switchTo()->window($name);
    }

    /**
     * @param  array           $spec
     * @throws DriverException
     */
    public function frame($spec)
    {
        if (!isset($spec['id'])) {
            throw new DriverException('Cannot switch frame, "id" was expected in spec.');
        }

        $this->webDriver->switchTo()->frame($spec['id']);
    }

    /**
     * @return array
     */
    public function window_handles()
    {
        return $this->webDriver->getWindowHandles();
    }

    /**
     * @return string
     */
    public function window_handle()
    {
        return $this->webDriver->getWindowHandle();
    }

    /**
     * @param  string          $type
     * @param  mixed           $query
     * @return WebDriverBy
     * @throws DriverException
     */
    private function buildLocator($type, $query)
    {
        $locatorBuilder = array(WebDriverBy::class, $type);
        if (is_callable($locatorBuilder)) {
            return call_user_func($locatorBuilder, $query);
        } else {
            throw new DriverException("Locator \"WebDriverBy::{$type}\" is not supported.");
        }
    }

    /**
     * @param  string                                 $type
     * @param  mixed                                  $query
     * @return \Facebook\WebDriver\WebDriverElement[]
     * @deprecated To be removed.
     */
    public function elements($type, $query)
    {
        return $this->webDriver->findElements($this->buildLocator($type, $query));
    }

    /**
     * @param  string                               $type
     * @param  mixed                                $query
     * @return \Facebook\WebDriver\WebDriverElement
     * @deprecated To be removed.
     */
    public function element($type, $query)
    {
        return $this->webDriver->findElement($this->buildLocator($type, $query));
    }

    /**
     * @param  mixed                                  $query
     * @return \Facebook\WebDriver\WebDriverElement[]
     */
    public function elementsByXpath($query)
    {
        return $this->webDriver->findElements(WebDriverBy::xpath($query));
    }

    /**
     * @param  mixed                                $query
     * @return \Facebook\WebDriver\WebDriverElement
     */
    public function elementByXpath($query)
    {
        return $this->webDriver->findElement(WebDriverBy::xpath($query));
    }

    /**
     * @param  string          $type
     * @param  int             $param
     * @throws DriverException
     */
    public function timeouts($type, $param)
    {
        switch ($type) {
            case 'script':
                $this->webDriver->manage()->timeouts()->setScriptTimeout($param);
                break;
            case 'implicit':
                $this->webDriver->manage()->timeouts()->implicitlyWait($param);
                break;
            case 'page':
                $this->webDriver->manage()->timeouts()->pageLoadTimeout($param);
                break;
            default:
                throw new DriverException("Timeout type \"{$type}\" is not supported.");
        }
    }

    public function moveto(WebDriverElement $element)
    {
        $this->webDriver->action()->moveToElement($element)->perform();
    }

    public function click()
    {
        $this->webDriver->getMouse()->click();
    }

    public function contextClick()
    {
        $this->webDriver->getMouse()->contextClick();
    }

    public function doubleclick()
    {
        $this->webDriver->getMouse()->doubleClick();
    }

    public function buttonup()
    {
        $this->webDriver->getMouse()->mouseUp();
    }

    public function buttondown()
    {
        $this->webDriver->getMouse()->mouseDown();
    }

    /**
     * @param  string                              $handle
     * @return \Facebook\WebDriver\WebDriverWindow
     */
    public function window($handle)
    {
        $this->webDriver->switchTo()->window($handle);
        return $this->webDriver->manage()->window();
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->webDriver->getSessionID();
    }
}
