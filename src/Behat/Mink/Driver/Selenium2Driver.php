<?php

namespace Behat\Mink\Driver;

use Behat\Mink\Session;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;

use RemoteWebDriver as WebDriver;
use WebDriverKeys as Key;

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Selenium2 driver.
 *
 * @author Pete Otaqui <pete@otaqui.com>
 * @author Till Klampaeckel <till@php.net>
 */
class Selenium2Driver extends CoreDriver
{
    /**
     * The current Mink session
     * @var \Behat\Mink\Session
     */
    private $session;

    /**
     * Whether the browser has been started
     * @var Boolean
     */
    private $started = false;

    /**
     * The WebDriver instance
     * @var WebDriver
     */
    private $webDriver;

    /**
     * The WebDriver host
     * @var string
     */
    private $wdHost;

    /**
     * @var bool
     */
    private $implicitWait = false;

    /**
     * @var string
     */
    private $browserName;

    /**
     * @var array
     */
    private $desiredCapabilities;

    /**
     * Instantiates the driver.
     *
     * @param string    $browserName Browser name
     * @param array     $desiredCapabilities The desired capabilities
     * @param string    $wdHost The WebDriver host
     */
    public function __construct($browserName = 'firefox', $desiredCapabilities = null, $wdHost = 'http://localhost:4444/wd/hub')
    {
        if (null === $desiredCapabilities) {
            $desiredCapabilities = array();
        }

        $this->wdHost = $wdHost;

        if (isset($desiredCapabilities['implicit_wait'])) {
            if (is_numeric($desiredCapabilities['implicit_wait'])) {
                $this->implicitWait = $desiredCapabilities['implicit_wait'];
            }
            unset($desiredCapabilities['implicit_wait']);
        }

        if ('firefox' != $browserName) {
            $desiredCapabilities[\WebDriverCapabilityType::BROWSER_NAME] = $browserName;
            unset($desiredCapabilities['browser']);
        }

        if (empty($desiredCapabilities)) {
            $desiredCapabilities = null; // lol
        }

        $this->setBrowserName($browserName);
        $this->setDesiredCapabilities($desiredCapabilities);

        $this->start();
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
     * defaults as dsesired.
     *
     * See http://code.google.com/p/selenium/wiki/DesiredCapabilities
     *
     * @param   array $desiredCapabilities  an array of capabilities to pass on to the WebDriver server
     */
    public function setDesiredCapabilities($desiredCapabilities = null)
    {
        if (null === $desiredCapabilities) {
            $desiredCapabilities = self::getDefaultCapabilities();
        }
        if (isset($desiredCapabilities['firefox'])) {
            foreach ($desiredCapabilities['firefox'] as $capability => $value) {
                switch ($capability) {
                case 'profile':
                    $desiredCapabilities['firefox_'.$capability] = base64_encode(file_get_contents($value));
                    break;
                default:
                    $desiredCapabilities['firefox_'.$capability] = $value;
                }
            }

            unset($desiredCapabilities['firefox']);
        }

        if (isset($desiredCapabilities['chrome'])) {
            foreach ($desiredCapabilities['chrome'] as $capability => $value) {
                $desiredCapabilities['chrome.'.$capability] = $value;
            }

            unset($desiredCapabilities['chrome']);
        }

        $this->desiredCapabilities = $desiredCapabilities;
    }

    /**
     * Sets the WebDriver instance
     *
     * @param WebDriver $webDriver An instance of the WebDriver class
     */
    public function setWebDriver(WebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
    }

    /**
     * Gets the WebDriverSession instance
     *
     * @return WebDriver
     */
    public function getWebDriverSession()
    {
        return $this->webDriver;
    }

    /**
     * Returns the default capabilities
     *
     * @return array
     */
    public static function getDefaultCapabilities()
    {
        return array(
            'browserName'       => 'firefox',
            'version'           => '9',
            'platform'          => 'ANY',
            'browserVersion'    => '9',
            'browser'           => 'firefox',
            'name'              => 'Behat Test',
            'deviceOrientation' => 'portrait',
            'deviceType'        => 'tablet',
            'selenium-version'  => '2.31.0'
        );
    }

    /**
     * Makes sure that the Syn event library has been injected into the current page,
     * and return $this for a fluid interface,
     *
     *     $this->withSyn()->executeJsOnXpath($xpath, $script);
     *
     * @return Selenium2Driver
     */
    protected function withSyn()
    {
        $hasSyn = $this->webDriver->executeScript(
            "return typeof window['Syn']!=='undefined';"
        );

        if (!$hasSyn) {
            $synJs = file_get_contents(__DIR__.'/Selenium2/syn.js');
            $this->webDriver->executeScript($synJs);
        }

        return $this;
    }

    /**
     * Creates some options for key events
     *
     * @param  string $event         the type of event ('keypress', 'keydown', 'keyup');
     * @param  string $char          the character or code
     * @param  string $modifier=null one of 'shift', 'alt', 'ctrl' or 'meta'
     *
     * @return string a json encoded options array for Syn
     */
    protected static function charToOptions($event, $char, $modifier=null)
    {
        $ord = ord($char);
        if (is_numeric($char)) {
            $ord  = $char;
            $char = chr($char);
        }

        $options = array(
            'keyCode'  => $ord,
            'charCode' => $ord
        );

        if ($modifier) {
            $options[$modifier.'Key'] = 1;
        }

        return json_encode($options);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the result of the $xpath query
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param  string   $xpath  the xpath to search with
     * @param  string   $script the script to execute
     * @param  Boolean  $sync   whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    protected function executeJsOnXpath($xpath, $script, $sync = true)
    {
        if (true === $sync) {
            $this->withSyn();
        }

        $by = \WebDriverBy::xpath($xpath);
        $element   = $this->webDriver->findElement($by);
        $subscript = "arguments[0]";

        $script  = str_replace('{{ELEMENT}}', $subscript, $script);

        return $this->webDriver->executeScript($script, array($element));
    }

    /**
     * @see Behat\Mink\Driver\DriverInterface::setSession()
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Starts driver.
     */
    public function start()
    {
        $webDriver = new WebDriver($this->wdHost, $this->desiredCapabilities);
        if (false !== $this->implicitWait) {
            $webDriver->manage()->timeouts()->implicitlyWait($this->implicitWait);
        }

        $this->setWebDriver($webDriver);

        $this->started = true;
    }

    /**
     * Checks whether driver is started.
     *
     * @return  Boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Stops driver.
     */
    public function stop()
    {
        if (!$this->webDriver) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = false;

        try {
            $this->webDriver->quit();
        } catch (\Exception $e) {
            throw new DriverException('Could not close connection', 0, $e);
        }
    }

    /**
     * Resets driver.
     */
    public function reset()
    {
        $this->webDriver->manage()->deleteAllCookies();
    }

    /**
     * Visit specified URL.
     *
     * @param   string  $url    url of the page
     */
    public function visit($url)
    {
        $this->webDriver->get($url);
    }

    /**
     * Returns current URL address.
     *
     * @return  string
     */
    public function getCurrentUrl()
    {
        return $this->webDriver->getCurrentURL();
    }

    /**
     * Reloads current page.
     */
    public function reload()
    {
        $this->webDriver->navigate()->refresh();
    }

    /**
     * Moves browser forward 1 page.
     */
    public function forward()
    {
        $this->webDriver->navigate()->forward();
    }

    /**
     * Moves browser backward 1 page.
     */
    public function back()
    {
        $this->webDriver->navigate()->back();
    }

    /**
     * Switches to specific browser window.
     *
     * @param string $name window name (null for switching back to main window)
     */
    public function switchToWindow($name = null)
    {
        $this->webDriver->switchTo()->window($name);
    }

    /**
     * Switches to specific iFrame.
     *
     * @param string $name iframe name (null for switching back)
     */
    public function switchToIFrame($name = null)
    {
        if (null === $name) {
            $this->webDriver->switchTo()->window($name);
            return;
        }
        $this->webDriver->switchTo()->frame($name);
    }

    /**
     * Sets cookie.
     *
     * @param   string  $name
     * @param   string  $value
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->webDriver->manage()->deleteCookieNamed($name);
            return;
        }

        $cookieArray = array(
            'name'   => $name,
            'value'  => (string) $value,
            'secure' => false, // thanks, chibimagic!
        );
        $this->webDriver->manage()->addCookie($cookieArray);
    }

    /**
     * Returns cookie by name.
     *
     * @param   string  $name
     *
     * @return  string|null
     */
    public function getCookie($name)
    {
        $cookies = $this->webDriver->manage()->getCookies();
        foreach ($cookies as $cookie) {
            if ($cookie['name'] === $name) {
                return urldecode($cookie['value']);
            }
        }
    }

    /**
     * Returns last response content.
     *
     * @return  string
     */
    public function getContent()
    {
        return $this->webDriver->getPageSource();
    }

    /**
     * Capture a screenshot of the current window.
     *
     * @return  string  screenshot of MIME type image/* depending
     *   on driver (e.g., image/png, image/jpeg)
     */
    public function getScreenshot()
    {
        return base64_decode($this->webDriver->takeScreenshot());
    }

    /**
     * Finds elements with specified XPath query.
     *
     * @param   string  $xpath
     *
     * @return  array   array of Behat\Mink\Element\NodeElement
     */
    public function find($xpath)
    {
        $by = \WebDriverBy::xpath($xpath);
        $nodes = $this->webDriver->findElements($by);
        $elements = array();

        foreach ($nodes as $i => $node) {
            $elements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $i+1), $this->session);
        }

        return $elements;
    }

    /**
     * Returns element's tag name by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  string
     */
    public function getTagName($xpath)
    {
        $by = \WebDriverBy::xpath($xpath);
        return $this->webDriver->findElement($by)->getTagName();
    }

    /**
     * Returns element's text by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  string
     */
    public function getText($xpath)
    {
        $by = \WebDriverBy::xpath($xpath);
        $node = $this->webDriver->findElement($by);
        $text = $node->getText();
        $text = (string) str_replace(array("\r", "\r\n", "\n"), ' ', $text);

        return $text;
    }

    /**
     * Returns element's html by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  string
     */
    public function getHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.innerHTML;');
    }

    /**
     * Returns element's attribute by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  mixed
     */
    public function getAttribute($xpath, $name)
    {
        $by = \WebDriverBy::xpath($xpath);
        $attribute = $this->webDriver->findElement($by)->getAttribute($name);
        if ('' !== $attribute) {
            return $attribute;
        }
    }

    /**
     * Returns element's value by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  mixed
     */
    public function getValue($xpath)
    {
        $script = file_get_contents(__DIR__ . '/Selenium2/value.js');
        $value = $this->executeJsOnXpath($xpath, $script);
        if ($value) {
            if (preg_match('/^string:(.*)$/ms', $value, $vars)) {
                return $vars[1];
            }
            if (preg_match('/^boolean:(.*)$/', $value, $vars)) {
                return 'true' === strtolower($vars[1]);
            }
            if (preg_match('/^array:(.*)$/', $value, $vars)) {
                if ('' === trim($vars[1])) {
                    return array();
                }

                return explode(',', $vars[1]);
            }
        }
    }

    /**
     * Sets element's value by it's XPath query.
     *
     * @param   string  $xpath
     * @param   string  $value
     */
    public function setValue($xpath, $value)
    {
        $by = \WebDriverBy::xpath($xpath);
        $value = strval($value);
        $element = $this->webDriver->findElement($by);
        $elementname = strtolower($element->getTagName());

        switch (true) {
        case ($elementname == 'input' && strtolower($element->getAttribute('type')) == 'text'):
            for ($i = 0; $i < strlen($element->getAttribute('value')); $i++) {
                $value = Key::BACKSPACE . $value;
            }
            break;
        case ($elementname == 'textarea'):
        case ($elementname == 'input' && strtolower($element->getAttribute('type')) != 'file'):
            $element->clear();
            break;
        case ($elementname == 'select'):
            $this->selectOption($xpath, $value);
            return;
        }

        $element->sendKeys($value);
    }

    /**
     * Checks checkbox by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function check($xpath)
    {
        $this->executeJsOnXpath($xpath, '{{ELEMENT}}.checked = true');
        $script = "Syn.trigger('change', {}, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Unchecks checkbox by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function uncheck($xpath)
    {
        $this->executeJsOnXpath($xpath, '{{ELEMENT}}.checked = false');
        $script = "Syn.trigger('change', {}, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Checks whether checkbox checked located by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  Boolean
     */
    public function isChecked($xpath)
    {
        $by = \WebDriverBy::xpath($xpath);
        return $this->webDriver->findElement($by)->isSelected();
    }

    /**
     * Selects option from select field located by it's XPath query.
     *
     * @param   string  $xpath
     * @param   string  $value
     * @param   Boolean $multiple
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $valueEscaped = str_replace('"', '\"', $value);
        $multipleJS   = $multiple ? 'true' : 'false';

        $script = file_get_contents(__DIR__ . '/Selenium2/select.js');

        $script = str_replace(
            array('{{valueEscaped}}', '{{multipleJS}}'),
            array($valueEscaped, $multipleJS),
            $script
        );

        $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * Clicks button or link located by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function click($xpath)
    {
        $by = \WebDriverBy::xpath($xpath);
        $this->webDriver->findElement($by)->click();
    }

    /**
     * Double-clicks button or link located by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function doubleClick($xpath)
    {
        $script = 'Syn.dblclick({{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Right-clicks button or link located by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function rightClick($xpath)
    {
        $script = 'Syn.rightClick({{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Attaches file path to file field located by it's XPath query.
     *
     * @param   string  $xpath
     * @param   string  $path
     */
    public function attachFile($xpath, $path)
    {
        $by = \WebDriverBy::xpath($xpath);
        $this->webDriver->findElement($by)->sendKeys($path);
    }

    /**
     * Checks whether element visible located by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  Boolean
     */
    public function isVisible($xpath)
    {
        $by = \WebDriverBy::xpath($xpath);
        return $this->webDriver->findElement($by)->isDisplayed();
    }

    /**
     * Simulates a mouse over on the element.
     *
     * @param   string  $xpath
     */
    public function mouseOver($xpath)
    {
        $script = "Syn.trigger('mouseover', {}, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Brings focus to element.
     *
     * @param   string  $xpath
     */
    public function focus($xpath)
    {
        $script = "Syn.trigger('focus', {}, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Removes focus from element.
     *
     * @param   string  $xpath
     */
    public function blur($xpath)
    {
        $script = "Syn.trigger('blur', {}, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Presses specific keyboard key.
     *
     * @param   string  $xpath
     * @param   mixed   $char       could be either char ('b') or char-code (98)
     * @param   string  $modifier   keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions('keypress', $char, $modifier);
        $script = "Syn.trigger('keypress', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Pressed down specific keyboard key.
     *
     * @param   string  $xpath
     * @param   mixed   $char       could be either char ('b') or char-code (98)
     * @param   string  $modifier   keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions('keydown', $char, $modifier);
        $script = "Syn.trigger('keydown', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Pressed up specific keyboard key.
     *
     * @param   string  $xpath
     * @param   mixed   $char       could be either char ('b') or char-code (98)
     * @param   string  $modifier   keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions('keyup', $char, $modifier);
        $script = "Syn.trigger('keyup', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }


    /**
     * Drag one element onto another.
     *
     * @param   string  $sourceXpath
     * @param   string  $destinationXpath
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source      = $this->webDriver->findElement(\WebDriverBy::xpath($sourceXpath));
        $destination = $this->webDriver->findElement(\WebDriverBy::xpath($destinationXpath));

        $this->webDriver
            ->action()
            ->dragAndDrop($source, $destination)
            ->perform();
    }

    /**
     * Executes JS script.
     *
     * @param   string  $script
     */
    public function executeScript($script)
    {
        $this->webDriver->executeScript($script);
    }

    /**
     * Evaluates JS script.
     *
     * @param   string  $script
     *
     * @return  mixed           script return value
     */
    public function evaluateScript($script)
    {
        return $this->webDriver->executeScript($script);
    }

    /**
     * Waits some time or until JS condition turns true.
     *
     * @param   integer $time       time in milliseconds
     * @param   string  $condition  JS condition
     *
     * @return boolean
     */
    public function wait($time, $condition)
    {
        $script = "return $condition;";
        $start = microtime(true);
        $end = $start + $time / 1000.0;

        do {
            $result = $this->webDriver->executeScript($script);
            usleep(100000);
        } while ( microtime(true) < $end && !$result );

        return (bool)$result;
    }

    /**
     * Set the dimensions of the window.
     *
     * @param integer $width set the window width, measured in pixels
     * @param integer $height set the window height, measured in pixels
     * @param string $name window name (null for the current window)
     */
    public function resizeWindow($width, $height, $name = null)
    {
        if (null !== $name) {
            $this->webDriver->switchTo()->window($name);
        }

        $dimensions = new \WebDriverDimension($width, $height);
        return $this->webDriver->manage()->window()->setSize($dimensions);
    }
}
