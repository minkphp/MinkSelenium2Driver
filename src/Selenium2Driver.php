<?php

namespace Behat\Mink\Driver;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\KeyModifier;
use Behat\Mink\Selector\Xpath\Escaper;
use WebDriver\Element;
use WebDriver\Exception\InvalidArgument;
use WebDriver\Exception\NoSuchElement;
use WebDriver\Exception\StaleElementReference;
use WebDriver\Exception\UnknownCommand;
use WebDriver\Exception\UnknownError;
use WebDriver\Key;
use WebDriver\Session;
use WebDriver\WebDriver;
use WebDriver\Window;

class Selenium2Driver extends CoreDriver
{
    // ... (previous code remains unchanged)

    public function setTimeouts(array $timeouts)
    {
        $this->timeouts = $timeouts;

        if ($this->isStarted()) {
            try {
                $this->applyTimeouts();
            } catch (UnknownError $e) {
                // Selenium 2.x.
                throw new DriverException('Error setting timeout: ' . $e->getMessage(), 0, $e);
            } catch (InvalidArgument $e) {
                // Selenium 3.x.
                throw new DriverException('Error setting timeout: ' . $e->getMessage(), 0, $e);
            }
        }
    }

    private function applyTimeouts(): void
    {
        foreach ($this->timeouts as $type => $param) {
            $this->getWebDriverSession()->timeouts($type, $param);
        }
    }

    // ... (rest of the code remains unchanged)
}