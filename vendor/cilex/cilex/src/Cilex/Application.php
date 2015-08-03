<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex;

use \Symfony\Component\Console;
use \Cilex\Provider\Console\ConsoleServiceProvider;

/**
 * The Cilex framework class.
 *
 * @author Mike van Riel <mike.vanriel@naenius.com>
 *
 * @api
 */
class Application extends \Pimple
{
    /**
     * Version number for Cilex
     */
    const VERSION = '1.0.0';

    /**
     * Registers the autoloader and necessary components.
     *
     * @param string      $name    Name for this application.
     * @param string|null $version Version number for this application.
     */
    public function __construct($name, $version = null, array $values = array())
    {
        parent::__construct();

        $consoleConfig = array('console.name' => $name);
        if (null !== $version) {
            $consoleConfig['console.version'] = $version;
        }
        $this->register(new ConsoleServiceProvider(), $consoleConfig);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * Executes this application.
     *
     * @param bool $interactive runs in an interactive shell if true.
     *
     * @return void
     */
    public function run($interactive = false)
    {
        $app = $this['console'];
        if ($interactive) {
            $app = new Console\Shell($app);
        }

        $app->run();
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     *
     * @param \Cilex\Command\Command $command A Command object
     *
     * @api
     *
     * @return void
     */
    public function command(Console\Command\Command $command)
    {
        $this['console']->add($command);
    }

    /**
     * Registers a service provider.
     *
     * @param \Cilex\ServiceProviderInterface|\Silex\ServiceProviderInterface $provider
     *     A ServiceProviderInterface instance
     * @param mixed[]                                                         $values
     *     An array of values that customizes the provider
     *
     * @return void
     */
    public function register($provider, array $values = array())
    {
        if ((!$provider instanceof \Cilex\ServiceProviderInterface)
            && (!$provider instanceof \Silex\ServiceProviderInterface)
        ) {
            throw new \InvalidArgumentException(
                'Extensions should implement either Cilex or Silex\' ServiceProviderInterface'
            );
        }

        $provider->register($this);

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }
}
