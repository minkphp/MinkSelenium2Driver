<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Session;
use WebDriver\Element;
use WebDriver\Exception\NoSuchElement;
use WebDriver\Exception\UnknownError;
use WebDriver\Exception;
use WebDriver\Key;
use WebDriver\WebDriver;

/**
 * Selenium2 driver.
 *
 * @author Pete Otaqui <pete@otaqui.com>
 */
class Selenium2Driver extends CoreDriver
{
    /**
     * The current Mink session
     * @var Session
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
     * @var string
     */
    private $browserName;

    /**
     * @var array
     */
    private $desiredCapabilities;

    /**
     * The WebDriverSession instance
     * @var \WebDriver\Session
     */
    private $wdSession;

    /**
     * The timeout configuration
     * @var array
     */
    private $timeouts = array();

    /**
     * Instantiates the driver.
     *
     * @param string $browserName         Browser name
     * @param array  $desiredCapabilities The desired capabilities
     * @param string $wdHost              The WebDriver host
     */
    public function __construct($browserName = 'firefox', $desiredCapabilities = null, $wdHost = 'http://localhost:4444/wd/hub')
    {
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
     * Sets the desired capabilities - called on construction.  If null is provided, will set the
     * defaults as desired.
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
     * @return \WebDriver\Session
     */
    public function getWebDriverSession()
    {
        return $this->wdSession;
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
        $hasSyn = $this->wdSession->execute(array(
            'script' => 'return typeof window["Syn"]!=="undefined" && typeof window["Syn"].trigger!=="undefined"',
            'args'   => array()
        ));

        if (!$hasSyn) {
            $synJs = file_get_contents(__DIR__.'/Selenium2/syn.js');
            $this->wdSession->execute(array(
                'script' => $synJs,
                'args'   => array()
            ));
        }

        return $this;
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
     * @param string  $xpath  the xpath to search with
     * @param string  $script the script to execute
     * @param Boolean $sync   whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    protected function executeJsOnXpath($xpath, $script, $sync = true)
    {
        return $this->executeJsOnElement($this->findElement($xpath), $script, $sync);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the element
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param Element $element the webdriver element
     * @param string  $script  the script to execute
     * @param Boolean $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(Element $element, $script, $sync = true)
    {
        $script  = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

        $options = array(
            'script' => $script,
            'args'   => array(array('ELEMENT' => $element->getID())),
        );

        if ($sync) {
            return $this->wdSession->execute($options);
        }

        return $this->wdSession->execute_async($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        try {
            $this->wdSession = $this->webDriver->session($this->browserName, $this->desiredCapabilities);
            $this->applyTimeouts();
        } catch (\Exception $e) {
            throw new DriverException('Could not open connection: '.$e->getMessage(), 0, $e);
        }

        if (!$this->wdSession) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }
        $this->started = true;
    }

    /**
     * Sets the timeouts to apply to the webdriver session
     *
     * @param array $timeouts The session timeout settings: Array of {script, implicit, page} => time in microsecconds
     *
     * @throws DriverException
     */
    public function setTimeouts($timeouts)
    {
        $this->timeouts = $timeouts;

        if ($this->isStarted()) {
            $this->applyTimeouts();
        }
    }

    /**
     * Applies timeouts to the current session
     */
    private function applyTimeouts()
    {
        try {
            foreach ($this->timeouts as $type => $param) {
                $this->wdSession->timeouts($type, $param);
            }
        } catch (UnknownError $e) {
            throw new DriverException('Error setting timeout: ' . $e->getMessage(), 0, $e);
        }
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
        if (!$this->wdSession) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = false;
        try {
            $this->wdSession->close();
        } catch (\Exception $e) {
            throw new DriverException('Could not close connection', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->wdSession->deleteAllCookies();
    }

    /**
     * {@inheritdoc}
     */
    public function visit($url)
    {
        $this->wdSession->open($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        return $this->wdSession->url();
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        $this->wdSession->refresh();
    }

    /**
     * {@inheritdoc}
     */
    public function forward()
    {
        $this->wdSession->forward();
    }

    /**
     * {@inheritdoc}
     */
    public function back()
    {
        $this->wdSession->back();
    }

    /**
     * {@inheritdoc}
     */
    public function switchToWindow($name = null)
    {
        $this->wdSession->focusWindow($name ? $name : '');
    }

    /**
     * {@inheritdoc}
     */
    public function switchToIFrame($name = null)
    {
        $this->wdSession->frame(array('id' => $name));
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null)
    {
        if (null === $value) {
            $this->wdSession->deleteCookie($name);

            return;
        }

        $cookieArray = array(
            'name'   => $name,
            'value'  => (string) $value,
            'secure' => false, // thanks, chibimagic!
        );

        $this->wdSession->setCookie($cookieArray);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->wdSession->source();
    }

    /**
     * {@inheritdoc}
     */
    public function getScreenshot()
    {
        return base64_decode($this->wdSession->screenshot());
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowNames()
    {
        return $this->wdSession->window_handles();
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowName()
    {
        return $this->wdSession->window_handle();
    }

    /**
     * {@inheritdoc}
     */
    public function find($xpath)
    {
        $nodes = $this->wdSession->elements('xpath', $xpath);

        $elements = array();
        foreach ($nodes as $i => $node) {
            $elements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $i+1), $this->session);
        }

        return $elements;
    }

    /**
     * {@inheritdoc}
     */
    public function getTagName($xpath)
    {
        return $this->findElement($xpath)->name();
    }

    /**
     * {@inheritdoc}
     */
    public function getText($xpath)
    {
        $node = $this->findElement($xpath);
        $text = $node->text();
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
        $script = 'return {{ELEMENT}}.getAttribute(' . json_encode((string) $name) . ')';

        return $this->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($xpath)
    {
        $script = <<<JS
var node = {{ELEMENT}},
    tagName = node.tagName.toLowerCase(),
    value = null;

if (tagName == 'input' || tagName == 'textarea') {
    var type = node.getAttribute('type');
    if (type == 'checkbox') {
        value = node.checked;
    } else if (type == 'radio') {
        var name = node.getAttribute('name');
        if (name) {
            var fields = window.document.getElementsByName(name),
                i, l = fields.length;
            for (i = 0; i < l; i++) {
                var field = fields.item(i);
                if (field.checked) {
                    value = field.value;
                    break;
                }
            }
        }
    } else {
        value = node.value;
    }
} else if (tagName == 'select') {
    if (node.getAttribute('multiple')) {
        value = [];
        for (var i = 0; i < node.options.length; i++) {
            if (node.options[i].selected) {
                value.push(node.options[i].value);
            }
        }
    } else {
        var idx = node.selectedIndex;
        if (idx >= 0) {
            value = node.options.item(idx).value;
        } else {
            value = null;
        }
    }
} else {
    var attributeValue = node.getAttribute('value');
    if (attributeValue != null) {
        value = attributeValue;
    } else if (node.value) {
        value = node.value;
    }
}

return JSON.stringify(value);
JS;

        return json_decode($this->executeJsOnXpath($xpath, $script), true);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->name());
        $elementType = strtolower($element->attribute('type'));
        $ignoreInputTypes = array('submit', 'image', 'button', 'reset');

        if ('input' === $elementName && in_array($elementType, $ignoreInputTypes)) {
            throw new DriverException(sprintf('Impossible to set value an element with XPath "%s" as it is not a select, textarea or textbox', $xpath));
        }

        if ('input' === $elementName && 'checkbox' === $elementType) {
            if ($element->selected() xor (bool) $value) {
                $this->click($xpath);
            }

            return;
        }

        $value = strval($value);

        if ('input' === $elementName && 'file' === $elementType) {
            $this->attachFile($xpath, $value);

            return;
        }

        if ('select' === $elementName || ('input' === $elementName && 'radio' === $elementType)) {
            $this->selectOption($xpath, $value);

            return;
        }

        if (in_array($elementName, array('input', 'textarea'))) {
            $existingValueLength = strlen($element->attribute('value'));
            // Add the TAB key to ensure we unfocus the field as browsers are triggering the change event only
            // after leaving the field.
            $value = str_repeat(Key::BACKSPACE . Key::DELETE, $existingValueLength) . $value . Key::TAB;
        }

        $element->postValue(array('value' => array($value)));
    }

    /**
     * {@inheritdoc}
     */
    public function check($xpath)
    {
        $this->ensureCheckboxElement($xpath);

        if ($this->isChecked($xpath)) {
            return;
        }

        $this->click($xpath);
    }

    /**
     * {@inheritdoc}
     */
    public function uncheck($xpath)
    {
        $this->ensureCheckboxElement($xpath);

        if (!$this->isChecked($xpath)) {
            return;
        }

        $this->click($xpath);
    }

    /**
     * {@inheritdoc}
     */
    public function isChecked($xpath)
    {
        return $this->findElement($xpath)->selected();
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $element = $this->findElement($xpath);
        $tagName = strtolower($element->name());

        if ('select' !== $tagName && !('input' === $tagName && 'radio' === $this->getAttribute($xpath, 'type'))) {
            throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
        }

        $valueEscaped = json_encode((string) $value);
        $multipleJS   = $multiple ? 'true' : 'false';

        $script = <<<JS
// Function to trigger an event. Cross-browser compliant. See http://stackoverflow.com/a/2490876/135494
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
};

var node = {{ELEMENT}},
    tagName = node.tagName.toLowerCase();
if (tagName == 'select') {
    var i, l = node.length;
    for (i = 0; i < l; i++) {
        if (node[i].value == $valueEscaped) {
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
        if (nodes[i].getAttribute('value') == $valueEscaped) {
            node.checked = true;
        }
    }
    if (tagName == 'input') {
      var type = node.getAttribute('type');
      if (type == 'radio') {
        triggerEvent(node, 'change');
      }
    }
}
JS;

        $this->executeJsOnElement($element, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        return $this->findElement($xpath)->selected();
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        $this->mouseOver($xpath);
        $this->wdSession->click(array('button' => 0));
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        $this->mouseOver($xpath);
        $this->wdSession->doubleclick();
    }

    /**
     * {@inheritdoc}
     */
    public function rightClick($xpath)
    {
        $this->mouseOver($xpath);
        $script = 'Syn.rightClick({{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        $element = $this->findElement($xpath);

        if ('input' !== strtolower($element->name()) || 'file' !== strtolower($this->getAttribute($xpath, 'type'))) {
            throw new DriverException(sprintf('Impossible to attach a file on the element with XPath "%s" as it is not a file input', $xpath));
        }

        $element->postValue(array('value' => str_split($path)));
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible($xpath)
    {
        return $this->findElement($xpath)->displayed();
    }

    /**
     * {@inheritdoc}
     */
    public function mouseOver($xpath)
    {
        $this->wdSession->moveto(array(
            'element' => $this->findElement($xpath)->getID()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function focus($xpath)
    {
        $script = 'Syn.trigger("focus", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function blur($xpath)
    {
        $script = 'Syn.trigger("blur", {}, {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $script = "Syn.trigger('keypress', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $script = "Syn.trigger('keydown', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $script = "Syn.trigger('keyup', $options, {{ELEMENT}})";
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }

    /**
     * {@inheritdoc}
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source      = $this->findElement($sourceXpath);
        $destination = $this->findElement($destinationXpath);

        $this->wdSession->moveto(array(
            'element' => $source->getID()
        ));

        $script = <<<JS
(function (element) {
    var event = document.createEvent("HTMLEvents");

    event.initEvent("dragstart", true, true);
    event.dataTransfer = {};

    element.dispatchEvent(event);
}({{ELEMENT}}));
JS;
        $this->withSyn()->executeJsOnElement($source, $script);

        $this->wdSession->buttondown();
        $this->wdSession->moveto(array(
            'element' => $destination->getID()
        ));
        $this->wdSession->buttonup();

        $script = <<<JS
(function (element) {
    var event = document.createEvent("HTMLEvents");

    event.initEvent("drop", true, true);
    event.dataTransfer = {};

    element.dispatchEvent(event);
}({{ELEMENT}}));
JS;
        $this->withSyn()->executeJsOnElement($destination, $script);
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

        $this->wdSession->execute(array('script' => $script, 'args' => array()));
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateScript($script)
    {
        if (0 !== strpos(trim($script), 'return ')) {
            $script = 'return ' . $script;
        }

        return $this->wdSession->execute(array('script' => $script, 'args' => array()));
    }

    /**
     * {@inheritdoc}
     */
    public function wait($timeout, $condition)
    {
        $script = "return $condition;";
        $start = microtime(true);
        $end = $start + $timeout / 1000.0;

        do {
            $result = $this->wdSession->execute(array('script' => $script, 'args' => array()));
            usleep(100000);
        } while (microtime(true) < $end && !$result);

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function resizeWindow($width, $height, $name = null)
    {
        $this->wdSession->window($name ? $name : 'current')->postSize(
            array('width' => $width, 'height' => $height)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        try {
            $this->findElement($xpath)->submit();
        } catch (Exception $e) {
            throw new DriverException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeWindow($name = null)
    {
        $this->wdSession->window($name ? $name : 'current')->maximize();
    }

    /**
     * Returns Session ID of WebDriver or `null`, when session not started yet.
     *
     * @return string|null
     */
    public function getWebDriverSessionId()
    {
        return $this->isStarted() ? basename($this->wdSession->getUrl()) : null;
    }

    /**
     * @param string $xpath
     *
     * @return Element
     *
     * @throws DriverException when the element is not found
     */
    private function findElement($xpath)
    {
        try {
            return $this->wdSession->element('xpath', $xpath);
        } catch (NoSuchElement $e) {
            throw new DriverException(sprintf('There is no element matching XPath "%s"', $xpath), 0, $e);
        }
    }

    /**
     * Ensures the element is a checkbox
     *
     * @param string $xpath
     *
     * @throws DriverException
     */
    private function ensureCheckboxElement($xpath)
    {
        if ('input' !== strtolower($this->getTagName($xpath)) || 'checkbox' !== $this->getAttribute($xpath, 'type')) {
            throw new DriverException(sprintf('Impossible to check the element with XPath "%s" as it is not a checkbox', $xpath));
        }
    }
}
