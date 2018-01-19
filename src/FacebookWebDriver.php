<?php

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SilverStripe\MinkFacebookWebDriver;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Selector\Xpath\Escaper;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverDimension;

/**
 * Facebook webdriver
 *
 * @author Pete Otaqui <pete@otaqui.com>
 */
class FacebookWebDriver extends CoreDriver
{
    /**
     * Default browser
     */
    const DEFAULT_BROWSER = 'chrome';

    /**
     * Hostname of driver
     *
     * @var string
     */
    private $webDriverHost = null;

    /**
     * Whether the browser has been started
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
     * @var DesiredCapabilities
     */
    private $desiredCapabilities;

    /**
     * The timeout configuration
     * @var array
     */
    private $timeouts = [];

    /**
     * @var Escaper
     */
    private $xpathEscaper;

    /**
     * Instantiates the driver.
     *
     * @param string $browserName Browser name
     * @param array $desiredCapabilities The desired capabilities
     * @param string $wdHost The WebDriver host
     */
    public function __construct(
        $browserName = self::DEFAULT_BROWSER,
        $desiredCapabilities = [],
        $wdHost = 'http://localhost:4444/wd/hub'
    ) {
        $this->setBrowserName($browserName);
        $caps = $this->initCapabilities($desiredCapabilities);
        $this->setDesiredCapabilities($caps);
        $this->setWebDriverHost($wdHost);
        $this->xpathEscaper = new Escaper();
    }

    /**
     * @return string
     */
    protected function getWebDriverHost()
    {
        return $this->webDriverHost;
    }

    /**
     * @param string $webDriverHost
     * @return $this
     */
    protected function setWebDriverHost($webDriverHost)
    {
        $this->webDriverHost = $webDriverHost;
        return $this;
    }

    /**
     * Detect and assign appropriate browser capabilities
     *
     * @link https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities
     *
     * @param array $desiredCapabilities
     * @return DesiredCapabilities
     */
    protected function initCapabilities($desiredCapabilities = [])
    {
        // Build base capabilities
        $browserName = $this->getBrowserName();
        if ($browserName && method_exists(DesiredCapabilities::class, $browserName)) {
            /** @var DesiredCapabilities $caps */
            $caps = DesiredCapabilities::$browserName();
        } else {
            $caps = new DesiredCapabilities();
        }

        // Set defaults
        foreach ($this->getDefaultCapabilities() as $key => $value) {
            if (is_null($caps->getCapability($key))) {
                $caps->setCapability($key, $value);
            }
        }

        // Merge in other requested types
        foreach ($desiredCapabilities as $key => $value) {
            switch ($key) {
                case 'firefox':
                    $this->initFirefoxCapabilities($caps, $value);
                    break;
                case 'chrome':
                    $this->initChromeCapabilities($caps, $value);
                    break;
                default:
                    $caps->setCapability($key, $value);
                    break;
            }
        }

        return $caps;
    }

    /**
     * Get browser name
     *
     * @return string
     */
    protected function getBrowserName()
    {
        return $this->browserName;
    }

    /**
     * Sets the browser name
     *
     * @param string $browserName the name of the browser to start, default is 'chrome'
     * @return $this
     */
    protected function setBrowserName($browserName = self::DEFAULT_BROWSER)
    {
        $this->browserName = $browserName;
        return $this;
    }

    /**
     * Gets the desiredCapabilities
     *
     * @return DesiredCapabilities
     */
    public function getDesiredCapabilities()
    {
        return $this->desiredCapabilities;
    }

    /**
     * Sets the desired capabilities - called on construction.
     *
     * See http://code.google.com/p/selenium/wiki/DesiredCapabilities
     *
     * @param DesiredCapabilities $desiredCapabilities an array of capabilities to pass on to the WebDriver server
     * @return $this
     */
    public function setDesiredCapabilities(DesiredCapabilities $desiredCapabilities)
    {
        $this->desiredCapabilities = $desiredCapabilities;
        return $this;
    }

    /**
     * Init firefox specific capabilities
     *
     * @param DesiredCapabilities $caps
     * @param array $config Firefox specific config capabilities
     */
    protected function initFirefoxCapabilities(DesiredCapabilities $caps, $config)
    {
        // @todo - Support FirefoxProfile settings
    }

    /**
     * Init chrome specific capabilities
     *
     * @link https://sites.google.com/a/chromium.org/chromedriver/capabilities
     * @param DesiredCapabilities $caps
     * @param array $config
     */
    protected function initChromeCapabilities(DesiredCapabilities $caps, $config)
    {
        $chromeOptions = [];
        foreach ($config as $capability => $value) {
            if ($capability == 'switches') {
                $chromeOptions['args'] = $value;
            } else {
                $chromeOptions[$capability] = $value;
            }
            $caps->setCapability('chrome.'.$capability, $value);
        }

        $caps->setCapability('chromeOptions', $chromeOptions);
    }

    /**
     * @return RemoteWebDriver
     */
    public function getWebDriver()
    {
        return $this->webDriver;
    }

    /**
     * Sets the WebDriver instance
     *
     * @param RemoteWebDriver $webDriver An instance of the WebDriver class
     * @return $this
     */
    public function setWebDriver(RemoteWebDriver $webDriver)
    {
        $this->webDriver = $webDriver;
        return $this;
    }

    /**
     * Returns the default capabilities
     *
     * @link https://github.com/SeleniumHQ/selenium/wiki/DesiredCapabilities
     *
     * @return array
     */
    public static function getDefaultCapabilities()
    {
        return [
            'browserName'       => self::DEFAULT_BROWSER,
            'platform'          => 'ANY',
            'browser'           => self::DEFAULT_BROWSER,
            'name'              => 'Behat Test',
            'deviceOrientation' => 'portrait',
            'deviceType'        => 'tablet',
            'selenium-version'  => '3.5.3'
        ];
    }

    /**
     * Makes sure that the Syn event library has been injected into the current page,
     * and return $this for a fluid interface,
     *
     *     $this->withSyn()->executeJsOnXpath($xpath, $script);
     *
     * @return $this
     */
    protected function withSyn()
    {
        $hasSyn = $this->webDriver->executeScript(
            'return typeof window["Syn"]!=="undefined" && typeof window["Syn"].trigger!=="undefined"'
        );

        if (!$hasSyn) {
            $synJs = file_get_contents(__DIR__.'/Resources/syn.js');
            $this->webDriver->executeScript($synJs);
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
     * @param RemoteWebElement $element the webdriver element
     * @param string  $script  the script to execute
     * @param Boolean $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(RemoteWebElement $element, $script, $sync = true)
    {
        $script  = str_replace('{{ELEMENT}}', 'arguments[0]', $script);
        if ($sync) {
            return $this->webDriver->executeScript($script, [$element]);
        }
        return $this->webDriver->executeAsyncScript($script, [$element]);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        try {
            $driver = RemoteWebDriver::create($this->getWebDriverHost(), $this->getDesiredCapabilities());
            $this->setWebDriver($driver);
            $this->applyTimeouts();
        } catch (Exception $e) {
            throw new DriverException('Could not open connection: ' . $e->getMessage(), 0, $e);
        }
        $this->started = true;
    }

    /**
     * Sets the timeouts to apply to the webdriver session
     *
     * @param array $timeouts The session timeout settings: Array of {script, implicit, page} => time in milliseconds
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
     *
     * @throws DriverException
     */
    private function applyTimeouts()
    {
        try {
            $timeouts = $this->webDriver->manage()->timeouts();
            foreach ($this->timeouts as $type => $param) {
                switch ($type) {
                    case 'script':
                        $timeouts->setScriptTimeout($param / 1000);
                        break;
                    case 'implicit':
                        $timeouts->implicitlyWait($param / 1000);
                        break;
                    case 'page':
                        $timeouts->pageLoadTimeout($param / 1000);
                        break;
                    default:
                        throw new DriverException('Invalid timeout ' . $type);
                }
            }
        } catch (WebDriverException $e) {
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
        if (!$this->webDriver) {
            throw new DriverException('Could not connect to a Selenium 2 / WebDriver server');
        }

        $this->started = false;
        try {
            $this->webDriver->close();
        } catch (Exception $e) {
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
            $this->webDriver->switchTo()->frame($name);
        } else {
            $this->switchToWindow();
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

        $cookieArray = array(
            'name'   => $name,
            'value'  => urlencode($value),
            'secure' => false, // thanks, chibimagic!
        );

        $this->webDriver->manage()->addCookie($cookieArray);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie($name)
    {
        return $this->webDriver->manage()->getCookieNamed($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->webDriver->getPageSource();
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
            $elements[] = sprintf('(%s)[%d]', $xpath, $i+1);
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
        $node = $this->findElement($xpath);
        $text = $node->getText();
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
        $element = $this->findElement($xpath);
        $elementName = strtolower($element->getTagName());
        $elementType = strtolower($element->getAttribute('type'));

        // Getting the value of a checkbox returns its value if selected.
        if ('input' === $elementName && 'checkbox' === $elementType) {
            return $element->isSelected() ? $element->getAttribute('value') : null;
        }

        if ('input' === $elementName && 'radio' === $elementType) {
            $script = <<<JS
var node = {{ELEMENT}},
    value = null;

var name = node.getAttribute('name');
if (name) {
    var fields = window.document.getElementsByName(name),
        i, l = fields.length;
    for (i = 0; i < l; i++) {
        var field = fields.item(i);
        if (field.form === node.form && field.checked) {
            value = field.value;
            break;
        }
    }
}

return value;
JS;

            return $this->executeJsOnElement($element, $script);
        }

        // Using $element->attribute('value') on a select only returns the first selected option
        // even when it is a multiple select, so a custom retrieval is needed.
        if ('select' === $elementName && $element->getAttribute('multiple')) {
            $script = <<<JS
var node = {{ELEMENT}},
    value = [];

for (var i = 0; i < node.options.length; i++) {
    if (node.options[i].selected) {
        value.push(node.options[i].value);
    }
}

return value;
JS;

            return $this->executeJsOnElement($element, $script);
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
            if (is_array($value)) {
                $this->deselectAllOptions($element);

                foreach ($value as $option) {
                    $this->selectOptionOnElement($element, $option, true);
                }

                return;
            }

            $this->selectOptionOnElement($element, $value);

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
                $this->selectRadioValue($element, $value);

                return;
            }

            if ('file' === $elementType) {
                // @todo - Check if this is correct way to upload files
                $element->sendKeys($value);
                // $element->postValue(array('value' => array(strval($value))));

                return;
            }
        }

        $value = strval($value);

        if (in_array($elementName, array('input', 'textarea'))) {
            $element->clear();
        }

        $element->sendKeys($value);
        $this->trigger($xpath, 'change');
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
        return $this->findElement($xpath)->isSelected();
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $element = $this->findElement($xpath);
        $tagName = strtolower($element->getTagName());

        if ('input' === $tagName && 'radio' === strtolower($element->getAttribute('type'))) {
            $this->selectRadioValue($element, $value);

            return;
        }

        if ('select' === $tagName) {
            $this->selectOptionOnElement($element, $value, $multiple);

            return;
        }

        throw new DriverException(sprintf('Impossible to select an option on the element with XPath "%s" as it is not a select or radio input', $xpath));
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        return $this->findElement($xpath)->isSelected();
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        $this->clickOnElement($this->findElement($xpath));
    }

    /**
     * Perform click on a specified element
     *
     * @param RemoteWebElement $element
     */
    private function clickOnElement(RemoteWebElement $element)
    {
        $element->getLocationOnScreenOnceScrolledIntoView();
        $element->click();
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        $this->doubleClickOnElement($this->findElement($xpath));
    }

    /**
     * Move the mouse to the specified location, and double click on it
     *
     * @param RemoteWebElement $element
     */
    private function doubleClickOnElement(RemoteWebElement $element)
    {
        $element->getLocationOnScreenOnceScrolledIntoView();
        $this->webDriver->getMouse()->doubleClick($element->getCoordinates());
    }

    /**
     * {@inheritdoc}
     */
    public function rightClick($xpath)
    {
        $this->rightClickOnElement($this->findElement($xpath));
    }

    private function rightClickOnElement(RemoteWebElement $element)
    {
        $element->getLocationOnScreenOnceScrolledIntoView();
        $this->webDriver->getMouse()->contextClick($element->getCoordinates());
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        $element = $this->findElement($xpath);
        $this->ensureInputType($element, $xpath, 'file', 'attach a file on');

        // @todo - Check this is the correct way to upload files
        $element->sendKeys($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible($xpath)
    {
        return $this->findElement($xpath)->isDisplayed();
    }

    /**
     * {@inheritdoc}
     */
    public function mouseOver($xpath)
    {
        $this->mouseOverElement($this->findElement($xpath));
    }

    /**
     * Scroll to the given element and move the mouse over it
     *
     * @param RemoteWebElement $element
     */
    private function mouseOverElement(RemoteWebElement $element)
    {
        $element->getLocationOnScreenOnceScrolledIntoView();
        $this->webDriver->getMouse()->mouseMove($element->getCoordinates());
    }

    /**
     * {@inheritdoc}
     */
    public function focus($xpath)
    {
        $this->trigger($xpath, 'focus');
    }

    /**
     * {@inheritdoc}
     */
    public function blur($xpath)
    {
        $this->trigger($xpath, 'blur');
    }

    /**
     * {@inheritdoc}
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $this->trigger($xpath, 'keypress', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $this->trigger($xpath, 'keydown', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        $options = self::charToOptions($char, $modifier);
        $this->trigger($xpath, 'keyup', $options);
    }

    /**
     * {@inheritdoc}
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source = $this->findElement($sourceXpath);
        $destination = $this->findElement($destinationXpath);
        $this->webDriver->action()->dragAndDrop($source, $destination);
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
            $script = "return {$script};";
        }

        return $this->webDriver->executeScript($script);
    }

    /**
     * {@inheritdoc}
     */
    public function wait($timeout, $condition)
    {
        $start = microtime(true);
        $end = $start + $timeout / 1000.0;

        do {
            $result = $this->evaluateScript($condition);
            usleep(100000);
        } while (microtime(true) < $end && !$result);

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function resizeWindow($width, $height, $name = null)
    {
        // Note: Only 'current' window can be resized, so other names ignored
        if ($name && $name !== 'current') {
            throw new DriverException("Can not resize non-current window: {$name}");
        }
        $this
            ->webDriver
            ->manage()
            ->window()
            ->setSize(new WebDriverDimension($width, $height));
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        $this->findElement($xpath)->submit();
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeWindow($name = null)
    {
        // Note: Only 'current' window can be resized, so other names ignored
        if ($name && $name !== 'current') {
            throw new DriverException("Can not resize non-current window: {$name}");
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
        return $this->isStarted()
            ? $this->webDriver->getSessionID()
            : null;
    }

    /**
     * @param string $xpath XPath expression
     * @param RemoteWebElement|null $parent Optional parent element
     * @return RemoteWebElement
     */
    private function findElement($xpath, RemoteWebElement $parent = null)
    {
        $finder = WebDriverBy::xpath($xpath);
        return $parent
            ? $parent->findElement($finder)
            : $this->webDriver->findElement($finder);
    }

    /**
     * Selects a value in a radio button group
     *
     * @param RemoteWebElement $element An element referencing one of the radio buttons of the group
     * @param string  $value   The value to select
     *
     * @throws DriverException when the value cannot be found
     */
    private function selectRadioValue(RemoteWebElement $element, $value)
    {
        // short-circuit when we already have the right button of the group to avoid XPath queries
        if ($element->getAttribute('value') === $value) {
            $element->click();

            return;
        }

        $name = $element->getAttribute('name');

        if (!$name) {
            throw new DriverException(sprintf('The radio button does not have the value "%s"', $value));
        }

        $formId = $element->getAttribute('form');

        try {
            if (null !== $formId) {
                $xpath = <<<'XPATH'
//form[@id=%1$s]//input[@type="radio" and not(@form) and @name=%2$s and @value = %3$s]
|
//input[@type="radio" and @form=%1$s and @name=%2$s and @value = %3$s]
XPATH;

                $xpath = sprintf(
                    $xpath,
                    $this->xpathEscaper->escapeLiteral($formId),
                    $this->xpathEscaper->escapeLiteral($name),
                    $this->xpathEscaper->escapeLiteral($value)
                );
                $input = $this->findElement($xpath);
            } else {
                $xpath = sprintf(
                    './ancestor::form//input[@type="radio" and not(@form) and @name=%s and @value = %s]',
                    $this->xpathEscaper->escapeLiteral($name),
                    $this->xpathEscaper->escapeLiteral($value)
                );
                $input = $this->findElement($xpath, $element);
            }
        } catch (NoSuchElementException $e) {
            $message = sprintf('The radio group "%s" does not have an option "%s"', $name, $value);

            throw new DriverException($message, 0, $e);
        }

        $input->click();
    }

    /**
     * @param RemoteWebElement $element
     * @param string  $value
     * @param bool    $multiple
     */
    private function selectOptionOnElement(RemoteWebElement $element, $value, $multiple = false)
    {
        $escapedValue = $this->xpathEscaper->escapeLiteral($value);
        // The value of an option is the normalized version of its text when it has no value attribute
        $optionQuery = sprintf('.//option[@value = %s or (not(@value) and normalize-space(.) = %s)]', $escapedValue, $escapedValue);
        $option = $this->findElement($optionQuery);

        if ($multiple || !$element->getAttribute('multiple')) {
            if (!$option->isSelected()) {
                $option->click();
            }

            return;
        }

        // Deselect all options before selecting the new one
        $this->deselectAllOptions($element);
        $option->click();
    }

    /**
     * Deselects all options of a multiple select
     *
     * Note: this implementation does not trigger a change event after deselecting the elements.
     *
     * @param RemoteWebElement $element
     */
    private function deselectAllOptions(RemoteWebElement $element)
    {
        $script = <<<JS
var node = {{ELEMENT}};
var i, l = node.options.length;
for (i = 0; i < l; i++) {
    node.options[i].selected = false;
}
JS;

        $this->executeJsOnElement($element, $script);
    }

    /**
     * Ensures the element is a checkbox
     *
     * @param RemoteWebElement $element
     * @param string $xpath XPath to the element
     * @param string $type Required value of 'type' property on this input
     * @param string $action Descriptive action being performed on this element
     *
     * @throws DriverException
     */
    private function ensureInputType(RemoteWebElement $element, $xpath, $type, $action)
    {
        if ('input' !== $element->getTagName()
            || $type !== $element->getAttribute('type')
        ) {
            throw new DriverException(
                "Impossible to {$action} the element with XPath \"{$xpath}\" as it is not a {$type} input"
            );
        }
    }

    /**
     * @param string $xpath XPath to element to trigger event on
     * @param string $event Event name
     * @param string $options Options to pass to window.syn.trigger
     */
    private function trigger($xpath, $event, $options = '{}')
    {
        $script = 'window.Syn.trigger("' . $event . '", ' . $options . ', {{ELEMENT}})';
        $this->withSyn()->executeJsOnXpath($xpath, $script);
    }
}
