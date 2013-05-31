<?php

namespace Behat\Mink\Driver;

use Behat\Mink\Session;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use WebDriver\Element;
use WebDriver\WebDriver;
use WebDriver\Key;

/**
 * Selenium2 driver.
 *
 * @author Pete Otaqui <pete@otaqui.com>
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Selenium2Driver extends CoreDriver
{
    /**
     * The current Mink session
     *
     * @var \Behat\Mink\Session
     */
    private $session;
    /**
     * Whether the browser has been started
     *
     * @var Boolean
     */
    private $started = false;
    /**
     * The WebDriver instance
     *
     * @var WebDriver
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
     * The WebDriverSession instance
     *
     * @var \WebDriver\Session
     */
    private $wdSession;

    /**
     * Instantiates the driver.
     *
     * @param string $browserName Browser name
     * @param array $desiredCapabilities The desired capabilities
     * @param string $wdHost The WebDriver host
     */
    public function __construct(
        $browserName = 'firefox',
        $desiredCapabilities = null,
        $wdHost = 'http://localhost:4444/wd/hub'
    ) {
        $this->setBrowserName($browserName);
        $this->setDesiredCapabilities($desiredCapabilities);
        $this->setWebDriver(new WebDriver($wdHost));
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
     * Sets the desired capabilities - called on construction. If null is provided, will set the
     * defaults as dsesired.
     *
     * See http://code.google.com/p/selenium/wiki/DesiredCapabilities
     *
     * @param array $desiredCapabilities an array of capabilities to pass on to the WebDriver server
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
                        $desiredCapabilities['firefox_' . $capability] = base64_encode(file_get_contents($value));
                        break;
                    default:
                        $desiredCapabilities['firefox_' . $capability] = $value;
                }
            }

            unset($desiredCapabilities['firefox']);
        }

        if (isset($desiredCapabilities['chrome'])) {
            foreach ($desiredCapabilities['chrome'] as $capability => $value) {
                $desiredCapabilities['chrome.' . $capability] = $value;
            }

            unset($desiredCapabilities['chrome']);
        }

        $this->desiredCapabilities = $desiredCapabilities;
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
            'version' => '9',
            'platform' => 'ANY',
            'browserVersion' => '9',
            'browser' => 'firefox',
            'name' => 'Behat Test',
            'deviceOrientation' => 'portrait',
            'deviceType' => 'tablet',
            'selenium-version' => '2.31.0'
        );
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
     * @return \WebDriver\Session
     */
    public function getWebDriverSession()
    {
        return $this->wdSession;
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
        try {
            $this->wdSession = $this->webDriver->session($this->browserName, $this->desiredCapabilities);
        } catch (\Exception $e) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server', 0, $e);
        }
        $this->started = true;
    }

    /**
     * Checks whether driver is started.
     *
     * @return Boolean
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
        if (!$this->wdSession) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = false;
        try {
            $this->wdSession->close();
        } catch (\Exception $e) {
            throw new DriverException('Could not close connection');
        }
    }

    /**
     * Resets driver.
     */
    public function reset()
    {
        $this->wdSession->deleteAllCookies();
    }

    /**
     * Visit specified URL.
     *
     * @param string $url url of the page
     */
    public function visit($url)
    {
        $this->wdSession->open($url);
    }

    /**
     * Returns current URL address.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->wdSession->url();
    }

    /**
     * Reloads current page.
     */
    public function reload()
    {
        $this->wdSession->refresh();
    }

    /**
     * Moves browser forward 1 page.
     */
    public function forward()
    {
        $this->wdSession->forward();
    }

    /**
     * Moves browser backward 1 page.
     */
    public function back()
    {
        $this->wdSession->back();
    }

    /**
     * Switches to specific browser window.
     *
     * @param string $name window name (null for switching back to main window)
     */
    public function switchToWindow($name = null)
    {
        $this->wdSession->focusWindow($name ? $name : '');
    }

    /**
     * Switches to specific iFrame.
     *
     * @param string $name iframe name (null for switching back)
     */
    public function switchToIFrame($name = null)
    {
        $this->wdSession->frame(array('id' => $name));
    }

    /**
     * Sets cookie.
     *
     * @param string $name
     * @param string $value
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->wdSession->deleteCookie($name);

            return;
        }

        $cookieArray = array(
            'name' => $name,
            'value' => (string)$value,
            'secure' => false, // thanks, chibimagic!
        );

        $this->wdSession->setCookie($cookieArray);
    }

    /**
     * Returns cookie by name.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getCookie($name)
    {
        $cookies = $this->wdSession->getAllCookies();
        foreach ($cookies as $cookie) {
            if ($cookie['name'] === $name) {
                return urldecode($cookie['value']);
            }
        }
    }

    /**
     * Returns last response content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->wdSession->source();
    }

    /**
     * Capture a screenshot of the current window.
     *
     * @return string screenshot of MIME type image/* depending
     * on driver (e.g., image/png, image/jpeg)
     */
    public function getScreenshot()
    {
        return base64_decode($this->wdSession->screenshot());
    }

    /**
     * Finds elements with specified XPath query.
     *
     * @param string $xpath
     *
     * @return NodeElement[] Array of Behat\Mink\Element\NodeElement
     */
    public function find($xpath)
    {
        $nodes = $this->wdSession->elements('xpath', $xpath);

        $elements = array();
        foreach ($nodes as $i => $node) {
            $elements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $i + 1), $this->session);
        }

        return $elements;
    }

    /**
     * Returns element's tag name by it's XPath query.
     *
     * @param string $xpath
     *
     * @return string
     */
    public function getTagName($xpath)
    {
        return $this->wdSession->element('xpath', $xpath)->name();
    }

    /**
     * Returns element's text by it's XPath query.
     *
     * @param string $xpath
     *
     * @return string
     */
    public function getText($xpath)
    {
        $node = $this->wdSession->element('xpath', $xpath);
        $text = $node->text();
        $text = (string)str_replace(array("\r", "\r\n", "\n"), ' ', $text);

        return $text;
    }

    /**
     * Returns element's html by it's XPath query.
     *
     * @param string $xpath
     *
     * @return string
     */
    public function getHtml($xpath)
    {
        return $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.innerHTML;');
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the result of the $xpath query
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param string $xpath the xpath to search with
     * @param string $script the script to execute
     * @param Boolean $sync whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    protected function executeJsOnXpath($xpath, $script, $sync = true)
    {
        $element = $this->wdSession->element('xpath', $xpath);
        $elementID = $element->getID();
        $subscript = "arguments[0]";

        $script = str_replace('{{ELEMENT}}', $subscript, $script);
        $execute = ($sync) ? 'execute' : 'execute_async';

        return $this->wdSession->$execute(
            array(
                'script' => $script,
                'args' => array(array('ELEMENT' => $elementID))
            )
        );
    }

    /**
     * Returns element's attribute by it's XPath query.
     *
     * @param string $xpath Element's xpath
     *
     * @param string $name Attribute name
     *
     * @return string|null Attribute value
     */
    public function getAttribute($xpath, $name)
    {
        return $this->wdSession->element('xpath', $xpath)->attribute($name);
    }

    /**
     * Returns element's value by it's XPath query.
     *
     * @param string $xpath
     *
     * @throws \Behat\Mink\Exception\DriverException If input type is unknown
     * @return mixed
     */
    public function getValue($xpath)
    {
        $element = $this->wdSession->element('xpath', $xpath);
        switch (strtolower($element->name())) {
            case 'input':
                $type = strtolower($element->attribute('type')) ? : 'text';
                switch ($type) {
                    case 'checkbox':
                        return $element->selected();
                    case 'radio':
                        $name = $element->attribute('name');
                        if (empty($name)) {
                            return $element->selected() ? $element->attribute('value') : '';
                        } else {
                            /** @var Element[] $fields */
                            $fields = $this->wdSession->elements('name', $name);
                            foreach ($fields as $field) {
                                if ($field->selected()) {
                                    return $field->attribute('value');
                                }
                            }
                        }

                        return null;
                    case 'button':
                    case 'reset':
                    case 'submit':
                    case 'hidden':
                    case 'password':
                    case 'text':
                        return $element->attribute('value');
                    default:
                        throw new DriverException(sprintf('Unknown input type "%s"', $type));
                }
            case 'textarea':
                return $element->attribute('value');
            case 'select':
                /** @var Element[] $options */
                $options = $element->elements('tag name', 'option');
                if ($element->attribute('multiple')) {
                    $values = array();
                    foreach ($options as $option) {
                        if ($option->selected()) {
                            array_push($values, $option->attribute('value'));
                        }
                    }

                    return $values;
                } else {
                    foreach ($options as $option) {
                        if ($option->selected()) {
                            return $option->attribute('value');
                        }
                    }

                    return null;
                }
            default:
                return $element->attribute('value');
        }
    }

    /**
     * Sets element's value by it's XPath query.
     *
     * @param string $xpath
     * @param string $value
     */
    public function setValue($xpath, $value)
    {
        $value = strval($value);
        $element = $this->wdSession->element('xpath', $xpath);
        $elementname = strtolower($element->name());

        switch (true) {
            case ($elementname == 'input' && strtolower($element->attribute('type')) == 'text'):
                for ($i = 0; $i < strlen($element->attribute('value')); $i++) {
                    $value = Key::BACKSPACE . $value;
                }
                break;
            case ($elementname == 'textarea'):
            case ($elementname == 'input' && strtolower($element->attribute('type')) != 'file'):
                $element->clear();
                break;
            case ($elementname == 'select'):
                $this->selectOption($xpath, $value);
                return;
        }

        $element->value(array('value' => array($value)));
        $script = "Syn.trigger('change', {}, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Checks checkbox by it's XPath query.
     *
     * @param string $xpath
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
     * @param string $xpath
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
     * @param string $xpath
     *
     * @return Boolean
     */
    public function isChecked($xpath)
    {
        return $this->wdSession->element('xpath', $xpath)->selected();
    }

    /**
     * Selects option from select field located by it's XPath query.
     *
     * @param string $xpath
     * @param string $value
     * @param Boolean $multiple
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $valueEscaped = str_replace('"', '\"', $value);
        $multipleJS = $multiple ? 'true' : 'false';

        $script = <<<JS
// Function to triger an event. Cross-browser compliant. See http://stackoverflow.com/a/2490876/135494
var triggerEvent = function (element, eventName) {
    var event;
    if (document.createEvent) {
        event = document.createEvent("HTMLEvents");
        event.initEvent(eventName, true, true);
    } else {
        event = document.createEventObject();
        event.eventType = eventName;
    }

    event.eventName = eventName;

    if (document.createEvent) {
        element.dispatchEvent(event);
    } else {
        element.fireEvent("on" + event.eventType, event);
    }
}

var node = {{ELEMENT}}
if (node.tagName == 'SELECT') {
    var i, l = node.length;
    for (i = 0; i < l; i++) {
        if (node[i].value == "$valueEscaped") {
            node[i].selected = true;
        } else if (!$multipleJS) {
            node[i].selected = false;
        }
    }
    triggerEvent(node, 'change');

} else {
    var nodes = window.document.getElementsByName(node.getAttribute('name'));
    var i, l = nodes.length;
    for (i = 0; i < l; i++) {
        if (nodes[i].getAttribute('value') == "$valueEscaped") {
            node.checked = true;
        }
    }
    if (node.tagName == 'INPUT') {
      var type = node.getAttribute('type');
      if (type == 'radio') {
        triggerEvent(node, 'change');
      }
    }
}
JS;


        $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * Clicks button or link located by it's XPath query.
     *
     * @param string $xpath
     */
    public function click($xpath)
    {
        $this->wdSession->element('xpath', $xpath)->click('');
    }

    /**
     * Double-clicks button or link located by it's XPath query.
     *
     * @param string $xpath
     */
    public function doubleClick($xpath)
    {
        $script = 'Syn.dblclick({{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Makes sure that the Syn event library has been injected into the current page,
     * and return $this for a fluid interface,
     *
     * $this->withSyn()->executeJsOnXpath($xpath, $script);
     *
     * @return Selenium2Driver
     */
    protected function withSyn()
    {
        $hasSyn = $this->wdSession->execute(
            array(
                'script' => 'return typeof window["Syn"]!=="undefined"',
                'args' => array()
            )
        );

        if (!$hasSyn) {
            $synJs = file_get_contents(__DIR__ . '/Selenium2/syn.js');
            $this->wdSession->execute(
                array(
                    'script' => $synJs,
                    'args' => array()
                )
            );
        }

        return $this;
    }

    /**
     * Right-clicks button or link located by it's XPath query.
     *
     * @param string $xpath
     */
    public function rightClick($xpath)
    {
        $script = 'Syn.rightClick({{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Attaches file path to file field located by it's XPath query.
     *
     * @param string $xpath
     * @param string $path
     */
    public function attachFile($xpath, $path)
    {
        $this->wdSession->element('xpath', $xpath)->value(array('value' => str_split($path)));
    }

    /**
     * Checks whether element visible located by it's XPath query.
     *
     * @param string $xpath
     *
     * @return Boolean
     */
    public function isVisible($xpath)
    {
        return $this->wdSession->element('xpath', $xpath)->displayed();
    }

    /**
     * Simulates a mouse over on the element.
     *
     * @param string $xpath
     */
    public function mouseOver($xpath)
    {
        $script = 'Syn.trigger("mouseover", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Brings focus to element.
     *
     * @param string $xpath
     */
    public function focus($xpath)
    {
        $script = 'Syn.trigger("focus", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Removes focus from element.
     *
     * @param string $xpath
     */
    public function blur($xpath)
    {
        $script = 'Syn.trigger("blur", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Presses specific keyboard key.
     *
     * @param string $xpath
     * @param mixed $char could be either char ('b') or char-code (98)
     * @param string $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions('keypress', $char, $modifier);
        $script = "Syn.trigger('keypress', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * Creates some options for key events
     *
     * @param string $event the type of event ('keypress', 'keydown', 'keyup');
     * @param string $char the character or code
     * @param string $modifier=null one of 'shift', 'alt', 'ctrl' or 'meta'
     *
     * @return string a json encoded options array for Syn
     */
    protected static function charToOptions($event, $char, $modifier = null)
    {
        $ord = ord($char);
        if (is_numeric($char)) {
            $ord = $char;
            $char = chr($char);
        }

        $options = array(
            'keyCode' => $ord,
            'charCode' => $ord
        );

        if ($modifier) {
            $options[$modifier . 'Key'] = 1;
        }

        return json_encode($options);
    }

    /**
     * Pressed down specific keyboard key.
     *
     * @param string $xpath
     * @param mixed $char could be either char ('b') or char-code (98)
     * @param string $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
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
     * @param string $xpath
     * @param mixed $char could be either char ('b') or char-code (98)
     * @param string $modifier keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
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
     * @param string $sourceXpath
     * @param string $destinationXpath
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source = $this->wdSession->element('xpath', $sourceXpath);
        $destination = $this->wdSession->element('xpath', $destinationXpath);

        $this->wdSession->moveto(
            array(
                'element' => $source->getID()
            )
        );

        $script = <<<JS
(function (element) {
 var event = document.createEvent("HTMLEvents");

 event.initEvent("dragstart", true, true);
 event.dataTransfer = {};

 element.dispatchEvent(event);
}({{ELEMENT}}));
JS;
        $this->withSyn()->executeJsOnXpath($sourceXpath, $script);

        $this->wdSession->buttondown();
        $this->wdSession->moveto(
            array(
                'element' => $destination->getID()
            )
        );
        $this->wdSession->buttonup();

        $script = <<<JS
(function (element) {
    var event = document.createEvent("HTMLEvents");

    event.initEvent("drop", true, true);
    event.dataTransfer = {};

    element.dispatchEvent(event);
}({{ELEMENT}}));
JS;
        $this->withSyn()->executeJsOnXpath($destinationXpath, $script);
    }

    /**
     * Executes JS script.
     *
     * @param string $script
     */
    public function executeScript($script)
    {
        $this->wdSession->execute(array('script' => $script, 'args' => array()));
    }

    /**
     * Evaluates JS script.
     *
     * @param string $script
     *
     * @return mixed script return value
     */
    public function evaluateScript($script)
    {
        return $this->wdSession->execute(array('script' => $script, 'args' => array()));
    }

    /**
     * Waits some time or until JS condition turns true.
     *
     * @param integer $time time in milliseconds
     * @param string $condition JS condition
     */
    public function wait($time, $condition)
    {
        $script = "return $condition;";
        $start = microtime(true);
        $end = $start + $time / 1000.0;

        while (microtime(true) < $end && !$this->wdSession->execute(array('script' => $script, 'args' => array()))) {
            usleep(100000);
        }
    }

    /**
     * Set the dimensions of the window.
     *
     * @param integer $width set the window width, measured in pixels
     * @param integer $height set the window height, measured in pixels
     * @param string $name window name (null for the main window)
     */
    public function resizeWindow($width, $height, $name = null)
    {
        return $this->wdSession->window($name ? $name : 'current')->postSize(
            array('width' => $width, 'height' => $height)
        );
    }
}
