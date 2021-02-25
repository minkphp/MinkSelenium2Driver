<?php

/*
 * This file is part of the Behat MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SilverStripe\MinkFacebookWebDriver;

use Behat\MinkExtension\ServiceContainer\Driver\Selenium2Factory;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Factory required to provide facebook webriver support. This must be added to
 * {@see Behat\MinkExtension\ServiceContainer\MinkExtension} via registerDriverFactory().
 *
 * Base factory on older (but compatible config area) selenium 2 factory
 */
class FacebookFactory extends Selenium2Factory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'facebook_web_driver';
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        // Merge capabilities
        $extraCapabilities = $config['capabilities']['extra_capabilities'];
        unset($config['capabilities']['extra_capabilities']);

        // PATCH: Disable W3C mode in chromedriver until we have capacity to actively adopt it
        if (!empty($config['capabilities']['browser']) && $config['capabilities']['browser'] != WebDriverBrowserType::IE) {
            $extraCapabilities['chromeOptions'] = array_merge(
                isset($extraCapabilities['chromeOptions']) ? $extraCapabilities['chromeOptions'] : [],
                ['w3c' => false]
            );
        }

        $capabilities = array_replace($this->guessCapabilities(), $extraCapabilities, $config['capabilities']);

        // Build driver definition
        return new Definition(FacebookWebDriver::class, [
            $config['browser'],
            $capabilities,
            $config['wd_host'],
        ]);
    }

    /**
     * Guess capabilities from environment
     *
     * @return array
     */
    protected function guessCapabilities()
    {
        if (getenv('TRAVIS_JOB_NUMBER')) {
            return [
                'tunnel-identifier' => getenv('TRAVIS_JOB_NUMBER'),
                'build' => getenv('TRAVIS_BUILD_NUMBER'),
                'tags' => ['Travis-CI', 'PHP ' . phpversion()],
            ];
        }

        if (getenv('JENKINS_HOME')) {
            return [
                'tunnel-identifier' => getenv('JOB_NAME'),
                'build' => getenv('BUILD_NUMBER'),
                'tags' => ['Jenkins', 'PHP ' . phpversion(), getenv('BUILD_TAG')],
            ];
        }

        return [
            'tags' => [php_uname('n'), 'PHP ' . phpversion()],
        ];
    }

    protected function getCapabilitiesNode()
    {
        $node = parent::getCapabilitiesNode();
        // Override default browser to chrome
        $node
            ->children()
                ->scalarNode('browser')->defaultValue(FacebookWebDriver::DEFAULT_BROWSER)->end()
            ->end();
        return $node;
    }
}
