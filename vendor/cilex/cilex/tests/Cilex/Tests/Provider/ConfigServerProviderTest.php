<?php
/**
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Ben Selby <benmatselby@gmail.com>
 */

namespace Cilex\Tests\Provider;

use Cilex\Application;
use Cilex\Provider\ConfigServiceProvider;

/**
 * Test file for ConfigServiceProvider
 *
 * @author Ben Selby <benmatselby@gmail.com>
 */
class ConfigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test that an exception is thrown if the config path is not present
     * or valid
     *
     * @return void
     */
    public function testRegisterWillThrowExceptionIfConfigPathIsNotThere()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            __DIR__.'/../../../data/unknownfile is not a valid path to the configuration'
        );

        $app = new Application('Test');

        $app->register(
            new ConfigServiceProvider(),
            array(
                'config.path' =>  __DIR__.'/../../../data/unknownfile'
            )
        );

        $config = $app['config'];
    }

    /**
     * Test that the config provider can parse a json
     * configuration file
     *
     * @return void
     */
    public function testRegisterCanParseAJsonConfigFile()
    {
        $app = new Application('Test');

        $app->register(
            new ConfigServiceProvider(),
            array(
                'config.path' =>  __DIR__.'/../../../data/config.json'
            )
        );

        $config = $app['config'];
        $this->assertEquals($config->key, 'value');
    }

    public function testCanParseAPhpConfigFile()
    {
        $app = new Application('Test');

        $app->register(
            new ConfigServiceProvider(),
            array(
                'config.path' => __DIR__.'/../../../data/config.php'
            )
        );
        $config = $app['config'];
        $this->assertEquals($config['key'], 'value');
    }

    /**
     * Test that the config provider can throw an exception if
     * the json configuration file is invalid
     *
     * @return void
     */
    public function testRegisterThrowsExceptionInCaseOfInvalidJsonConfigFile()
    {
        $configPath = __DIR__.'/../../../data/config-invalid.json';

        $this->setExpectedException(
            'InvalidArgumentException',
            'Unable to decode the configuration file: ' . $configPath
        );

        $app = new Application('Test');

        $app->register(
            new ConfigServiceProvider(),
            array(
                'config.path' =>  $configPath
            )
        );

        $config = $app['config'];
    }

    /**
     * Test that register will throw an exception if an unknown
     * format is passed in
     *
     * @return void
     */
    public function testRegisterThrowsExceptionIfAnUnknownFormatIsPassed()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Unable to load configuration; the provided file extension was not recognized. Only yml, xml or json allowed'
        );

        $app = new Application('Test');

        $app->register(
            new ConfigServiceProvider(),
            array(
                'config.path' =>  __DIR__.'/../../../data/config.unknownfiletype'
            )
        );

        $config = $app['config'];
    }
}
