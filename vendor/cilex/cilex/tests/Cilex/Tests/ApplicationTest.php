<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex\Tests;

use \Cilex\Application;

/**
 * Mock class used to test the register method.
 */
class ServiceProviderMock implements \Cilex\ServiceProviderInterface
{
    /**
     * Mock method to satisfy interface
     *
     * @param \Cilex\Application $app
     *
     * @return void
     */
    function register(\Cilex\Application $app)
    {
        $app['mock.param'] = false;
        $app['mock'] = $this;
    }
}

/**
 * Application test cases.
 *
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    const NAME    = 'Test';
    const VERSION = '1.0.1';

    /** @var \Cilex\Application */
    protected $fixture = null;

    /**
     * Sets up the test fixture.
     */
    public function setUp()
    {
        $this->fixture = new Application(self::NAME, self::VERSION);
    }

    /**
     * Tests whether the constructor instantiates the correct dependencies and
     * correctly sets the name on the Console's Application.
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(
            '\\Symfony\\Component\\Console\\Application',
            $this->fixture['console']
        );

        $this->assertEquals(self::NAME, $this->fixture['console']->getName());
        $this->assertEquals(self::VERSION, $this->fixture['console']->getVersion());
    }

    /**
     * Tests the command method to see if the command is properly set and the
     * Cilex application is added as container.
     */
    public function testCommand()
    {
        $this->assertFalse($this->fixture['console']->has('demo:greet'));
        $this->fixture->command(new \Cilex\Command\GreetCommand());
        $this->assertTrue($this->fixture['console']->has('demo:greet'));

        $this->assertSame(
            $this->fixture,
            $this->fixture['console']->get('demo:greet')->getContainer()
        );
    }

    /**
     * Tests whether the register method applies the provided parameters to this
     * application and correctly registers the ServiceProvider.
     */
    public function testRegister()
    {
        $provider = new ServiceProviderMock();
        $this->fixture->register($provider, array('mock.param' => true));

        $this->assertTrue($this->fixture['mock.param']);
        $this->assertSame($this->fixture['mock'], $provider);
    }
}
