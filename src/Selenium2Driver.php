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
use Behat\Mink\Selector\Xpath\Escaper;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteTargetLocator;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverNavigation;
use Facebook\WebDriver\WebDriverOptions;
use Facebook\WebDriver\WebDriverRadios;
use Facebook\WebDriver\WebDriverSelect;

/**
 * Selenium2 driver.
 *
 * @author Pete Otaqui <pete@otaqui.com>
 */
class Selenium2Driver extends CoreDriver
{
    /**
     * Whether the browser has been started
     *
     * @var Boolean
     */
    private $started = false;

    /**
     * The WebDriver instance
     *
     * @var RemoteWebDriver
     */
    private $webDriver;

    /**
     * @var string
     */
    private $browserName;

    /**
     * @var array
     */
    private $desiredCapabilities;

    /**
     * The timeout configuration
     *
     * @var array
     */
    private $timeouts = array();

    /**
     * @var Escaper
     */
    private $xpathEscaper;

    /**
     * Wd host
     *
     * @var string
     */
    private $wdHost;
    private $navigation;
    private $action;
    private $switchTo;
    private $manage;

    /**
     * Instantiates the driver.
     *
     * @param string $browserName         Browser name
     * @param array  $desiredCapabilities The desired capabilities
     * @param string $wdHost              The WebDriver host
     */
    public function __construct($browserName = 'firefox', $desiredCapabilities = null, $wdHost = 'http://localhost:4444/wd/hub')
    {
        $this->wdHost = $wdHost;
        $this->browserName = $browserName;
        $this->setDesiredCapabilities($desiredCapabilities);
        $this->xpathEscaper = new Escaper();
    }

    /**
     * Sets the browser name
     *
     * @param string $browserName the name of the browser to start, default is 'firefox'
     */
    protected function setBrowserName($browserName = 'firefox')
    {
        $this->browserName = $browserName;
    }

    /**
     * Sets the desired capabilities - called on construction.  If null is provided, will set the
     * defaults as desired.
     *
     * See http://code.google.com/p/selenium/wiki/DesiredCapabilities
     *
     * @param array $desiredCapabilities an array of capabilities to pass on to the WebDriver server
     *
     * @throws DriverException
     */
    public function setDesiredCapabilities($desiredCapabilities = null)
    {
        if ($this->started) {
            throw new DriverException('Unable to set desiredCapabilities, the session has already started');
        }

        if (null === $desiredCapabilities) {
            $desiredCapabilities = array();
        }

        $desiredCapabilities = new DesiredCapabilities($desiredCapabilities);
        $desiredCapabilities->setBrowserName($this->browserName);

        $this->desiredCapabilities = $desiredCapabilities;
    }

    /**
     * Gets the desiredCapabilities
     *
     * @return array $desiredCapabilities
     */
    public function getDesiredCapabilities()
    {
        return $this->desiredCapabilities;
    }

    /**
     * Sets the WebDriver instance
     *
     * @param RemoteWebDriver $webDriver An instance of the WebDriver class
     */
    public function setWebDriver(RemoteWebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
    }

    /**
     * Returns the default capabilities
     *
     * @return array
     */
    public static function getDefaultCapabilities()
    {
        return array(
            'browserName' => 'firefox',
            'name'        => 'Behat Test',
        );
    }

    /**
     * Creates some options for key events
     *
     * @param string $char     the character or code
     * @param string $modifier one of 'shift', 'alt', 'ctrl' or 'meta'
     *
     * @return string a json encoded options array for Syn
     */
    protected static function charToOptions($char, $modifier = null)
    {
        $ord = ord($char);
        if (is_numeric($char)) {
            $ord = $char;
        }

        $options = array(
            'keyCode'  => $ord,
            'charCode' => $ord
        );

        if ($modifier) {
            $options[$modifier . 'Key'] = 1;
        }

        return json_encode($options);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the result of the $xpath query
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param string  $xpath  the xpath to search with
     * @param string  $script the script to execute
     * @param Boolean $sync   whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    protected function executeJsOnXpath($xpath, $script, $sync = true)
    {
        $element = $this->findElement($xpath);
        return $this->executeJsOnElement($element, $script, $sync);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the element
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param WebDriverElement $element the webdriver element
     * @param string           $script  the script to execute
     * @param Boolean          $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(WebDriverElement $element, $script, $sync = true)
    {
        $script = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

        if ($sync) {
            return $this->webDriver->executeScript($script, array(array('ELEMENT' => $element->getID())));
        }

        return $this->webDriver->executeAsyncScript($script, array(array('ELEMENT' => $element->getID())));
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        try {
            $this->webDriver = RemoteWebDriver::create($this->wdHost, $this->desiredCapabilities);
        } catch (\Exception $e) {
            throw new DriverException('Could not open connection: ' . $e->getMessage(), 0, $e);
        }

        if (!$this->webDriver) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        if (!$this->webDriver) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = false;
        try {
            $this->webDriver->quit();

            $this->webDriver = null;
            $this->navigation = null;
            $this->action = null;
            $this->switchTo = null;
            $this->manage = null;
        } catch (\Exception $e) {
            throw new DriverException('Could not close connection', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->webDriver->manage()->deleteAllCookies();
    }

    /**
     * {@inheritdoc}
     */
    public function visit($url)
    {
        $this->webDriver->navigate()->to($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        return $this->webDriver->getCurrentURL();
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->webDriver->navigate()->refresh();
    }

    /**
     * {@inheritdoc}
     */
    public function forward()
    {
        $this->webDriver->navigate()->forward();
    }

    /**
     * {@inheritdoc}
     */
    public function back()
    {
        $this->webDriver->navigate()->back();
    }

    /**
     * {@inheritdoc}
     */
    public function switchToWindow($name = null)
    {
        $this->webDriver->switchTo()->window($name);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToIFrame($name = null)
    {
        if ($name) {
            $element = $this->webDriver->findElement(WebDriverBy::name($name));
            $this->webDriver->switchTo()->frame($element);
        } else {
            $this->webDriver->switchTo()->defaultContent();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->webDriver->manage()->deleteCookieNamed($name);

            return;
        }

        $cookie = new Cookie($name, \urlencode($value));
        $this->webDriver->manage()->addCookie($cookie);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie($name)
    {
        $cookie = $this->webDriver->manage()->getCookieNamed($name);
        if (!$cookie) {
            return null;
        }

        return \urldecode($cookie->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $source = $this->webDriver->getPageSource();
        return str_replace(array("\r", "\r\n", "\n"), \PHP_EOL, $source);
    }

    /**
     * {@inheritdoc}
     */
    public function getScreenshot()
    {
        return $this->webDriver->takeScreenshot();
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowNames()
    {
        return $this->webDriver->getWindowHandles();
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowName()
    {
        return $this->webDriver->getWindowHandle();
    }

    /**
     * {@inheritdoc}
     */
    public function findElementXpaths($xpath)
    {
        $nodes = $this->webDriver->findElements(WebDriverBy::xpath($xpath));

        $elements = array();
        foreach ($nodes as $i => $node) {
            $elements[] = sprintf('(%s)[%d]', $xpath, $i + 1);
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagName($xpath)
    {
        $element = $this->findElement($xpath);
        return $element->getTagName();
    }

    /**
     * {@inheritdoc}
     */
    public function getText($xpath)
    {
        $element = $this->findElement($xpath);
        $text = $element->getText();

        $text = (string) str_replace(array("\r", "\r\n", "\n"), ' ', $text);

        return $text;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.innerHTML;');
    }

    /**
     * {@inheritdoc}
     */
    public function getOuterHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.outerHTML;');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($xpath, $name)
    {
        $element = $this->findElement($xpath);
        // https://w3c.github.io/webdriver/#get-element-attribute
        $attribute = $element->getAttribute($name);

        return $attribute ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($xpath)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->getTagName());
        $elementType = strtolower($element->getAttribute('type'));

        // Getting the value of a checkbox returns its value if selected.
        if ('input' === $elementName && 'checkbox' === $elementType) {
            return $element->isSelected() ? $element->getAttribute('value') : null;
        }

        if ('input' === $elementName && 'radio' === $elementType) {
            $element = new WebDriverRadios($element);
            return $element->getFirstSelectedOption()->getAttribute('value');
        }

        // Using $element->attribute('value') on a select only returns the first selected option
        // even when it is a multiple select, so a custom retrieval is needed.
        if ('select' === $elementName) {
            $element = new WebDriverSelect($element);
            if ($element->isMultiple()) {
                return \array_map(function (WebDriverElement $element) {
                    return $element->getAttribute('value');
                }, $element->getAllSelectedOptions());
            }

            return $element->getFirstSelectedOption()->getAttribute('value');
        }

        return $element->getAttribute('value');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->getTagName());

        if ('select' === $elementName) {
            $element = new WebDriverSelect($element);

            if (is_array($value)) {
                $element->deselectAll();
                foreach ($value as $option) {
                    $element->selectByValue($option);
                }

                return;
            }

            $element->selectByValue($value);

            return;
        }

        if ('input' === $elementName) {
            $elementType = strtolower($element->getAttribute('type'));

            if (in_array($elementType, array('submit', 'image', 'button', 'reset'))) {
                throw new DriverException(sprintf('Impossible to set value an element with XPath "%s" as it is not a select, textarea or textbox', $xpath));
            }

            if ('checkbox' === $elementType) {
                if ($element->isSelected() xor (bool) $value) {
                    $this->clickOnElement($element);
                }

                return;
            }

            if ('radio' === $elementType) {
                $element = new WebDriverRadios($element);
                $element->selectByValue($value);
                return;
            }

            if ('file' === $elementType) {
                $element->sendKeys($value);
                return;
            }
        }

        $value = strval($value);

        if (in_array($elementName, array('input', 'textarea'))) {
            $existingValueLength = strlen($element->getAttribute('value'));
            // Add the TAB key to ensure we unfocus the field as browsers are triggering the change event only
            // after leaving the field.
            $value = str_repeat(WebDriverKeys::BACKSPACE . WebDriverKeys::DELETE, $existingValueLength) . $value;
        }

        $element->sendKeys($value);
        // Remove the focus from the element if the field still has focus in
        // order to trigger the change event. By doing this instead of simply
        // triggering the change event for the given xpath we ensure that the
        // change event will not be triggered twice for the same element if it
        // has lost focus in the meanwhile. If the element has lost focus
        // already then there is nothing to do as this will already have caused
        // the triggering of the change event for that element.
        $element->sendKeys(WebDriverKeys::TAB);
    }

    /**
     * {@inheritdoc}
     */
    public function check($xpath)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'checkbox', 'check');

        if ($element->isSelected()) {
            return;
        }

        $this->clickOnElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function uncheck($xpath)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'checkbox', 'uncheck');

        if (!$element->isSelected()) {
            return;
        }

        $this->clickOnElement($element);
    }

    /**
     * {@inheritdoc}
     */
    public function isChecked($xpath)
    {
        return $this->isSelected($xpath);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $element = $this->findElement($xpath);
        $tagName = strtolower($element->getTagName());

        if ('input' === $tagName && 'radio' === strtolower($element->getAttribute('type'))) {
            $element = new WebDriverRadios($element);
            $element->selectByValue($value);
            return;
        }

        if ('select' === $tagName) {
            $element = new WebDriverSelect($element);
            if (!$multiple && $element->isMultiple()) {
                $element->deselectAll();
            }

            try {
                $element->selectByValue($value);
            } catch (NoSuchElementException $e) {
                // option may not have value attribute, so try to select by visible text
                $element->selectByVisibleText($value);
            }

            return;
        }

        throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        $element = $this->findElement($xpath);
        return $element->isSelected();
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        $element = $this->findElement($xpath);
        $this->clickOnElement($element);
    }

    private function clickOnElement(WebDriverElement $element)
    {
        // Move the mouse to the element as Selenium does not allow clicking on an element which is outside the viewport
        $this->webDriver->action()->click($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->doubleClick($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function rightClick($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->contextClick($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'file', 'attach a file on');

        $remotePath = $element->sendKeys($path);

        return $remotePath;
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible($xpath)
    {
        $element = $this->findElement($xpath);
        return $element->isDisplayed();
    }

    /**
     * {@inheritdoc}
     */
    public function mouseOver($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->moveToElement($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function focus($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->moveToElement($element)->click($element)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function blur($xpath)
    {
        $element = $this->findElement($xpath);
        $this->webDriver->action()->moveToElement($element)->sendKeys($element, WebDriverKeys::TAB)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        // @see https://w3c.github.io/uievents/#event-type-keypress
        $element = $this->findElement($xpath);
        $char = $this->decodeChar($char);
        $action = $this->webDriver->action();

        $keyboard = $this->webDriver->getKeyboard();
        $this->clickOnElement($element);
        if ($modifier) {
            $keyboard->pressKey($this->keyModifier($modifier));
        }
        $keyboard->sendKeys($char);
        if ($modifier) {
            $keyboard->releaseKey($this->keyModifier($modifier));
        }

        $action->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        // @see https://w3c.github.io/uievents/#event-type-keydown
        $element = $this->findElement($xpath);
        $char = $this->decodeChar($char);
        $action = $this->webDriver->action();

        $keyboard = $this->webDriver->getKeyboard();
        $this->clickOnElement($element);
        if ($modifier) {
            $keyboard->pressKey($this->keyModifier($modifier));
        }
        $keyboard->sendKeys($char);
        if ($modifier) {
            $keyboard->releaseKey($this->keyModifier($modifier));
        }

        $action->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        $element = $this->findElement($xpath);
        $char = $this->decodeChar($char);
        $action = $this->webDriver->action();

        $keyboard = $this->webDriver->getKeyboard();
        $this->clickOnElement($element);
        if ($modifier) {
            $keyboard->pressKey($this->keyModifier($modifier));
        }
        $keyboard->sendKeys($char);
        if ($modifier) {
            $keyboard->releaseKey($this->keyModifier($modifier));
        }

        $action->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source = $this->findElement($sourceXpath);
        $destination = $this->findElement($destinationXpath);
        $this->webDriver->action()->dragAndDrop($source, $destination)->perform();
    }

    /**
     * {@inheritdoc}
     */
    public function executeScript($script)
    {
        if (preg_match('/^function[\s\(]/', $script)) {
            $script = preg_replace('/;$/', '', $script);
            $script = '(' . $script . ')';
        }

        $this->webDriver->executeScript($script);
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateScript($script)
    {
        if (0 !== strpos(trim($script), 'return ')) {
            $script = 'return ' . $script;
        }

        return $this->webDriver->executeScript($script);
    }

    /**
     * {@inheritdoc}
     */
    public function wait($timeout, $condition)
    {
        $script = "return $condition;";
        $seconds = $timeout / 1000.0;

        $wait = $this->webDriver->wait($seconds, 100);

        return (bool) $wait->until(function (RemoteWebDriver $driver) use ($script) {
            return $driver->executeScript($script);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function resizeWindow($width, $height, $name = null)
    {
        $dimension = new WebDriverDimension($width, $height);
        if ($name) {
            throw new \Exception('Named windows are not supported yet');
        }

        $this->webDriver->manage()->window()->setSize($dimension);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        $element = $this->findElement($xpath);
        $element->submit();
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeWindow($name = null)
    {
        if ($name) {
            throw new \Exception('Named window is not supported');
        }

        $this->webDriver->manage()->window()->maximize();
    }

    /**
     * Returns Session ID of WebDriver or `null`, when session not started yet.
     *
     * @return string|null
     */
    public function getWebDriverSessionId()
    {
        return $this->webDriver->getSessionID();
    }

    /**
     * @param string $xpath
     *
     * @return WebDriverElement
     */
    private function findElement($xpath)
    {
        return $this->webDriver->findElement(WebDriverBy::xpath($xpath));
    }

    /**
     * Ensures the element is a checkbox
     *
     * @param WebDriverElement $element
     * @param string           $xpath
     * @param string           $type
     * @param string           $action
     *
     * @throws DriverException
     */
    private function  ensureInputType(WebDriverElement $element, $xpath, $type, $action)
    {
        if ('input' !== strtolower($element->getTagName()) || $type !== strtolower($element->getAttribute('type'))) {
            $message = 'Impossible to %s the element with XPath "%s" as it is not a %s input';

            throw new DriverException(sprintf($message, $action, $xpath, $type));
        }
    }

    /**
     * @param        $xpath
     * @param        $event
     * @param string $options
     */
    private function trigger($xpath, $event, $options = '{}')
    {
        $script = 'Syn.trigger("' . $event . '", ' . $options . ', {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    private function uploadFile($path)
    {
        throw new \RuntimeException('Not yet supported');
    }

    /**
     * Prepend modifier
     *
     * @param string $modifier
     *
     * @return string
     */
    private function keyModifier($modifier)
    {
        if ($modifier === 'alt') {
            $modifier = WebDriverKeys::ALT;
        } else if ($modifier === 'ctrl') {
            $modifier = WebDriverKeys::CONTROL;
        } else if ($modifier === 'shift') {
            $modifier = WebDriverKeys::SHIFT;
        } else if ($modifier === 'meta') {
            $modifier = WebDriverKeys::META;
        }

        return $modifier;
}

    /**
     * Decode char
     *
     * @param $char
     *
     * @return string
     */
    private function decodeChar($char)
    {
        if (\is_numeric($char)) {
            return \chr($char);
        }

        return $char;
    }
}
