<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex\Provider\Console;

use Symfony\Component\Console\Application;

/**
 * Cilex Pimple Console Application
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ContainerAwareApplication extends Application
{
    /** @var \Pimple */
    private $container;

    /**
     * Constructor
     *
     * @param string  $name      The name of the application
     * @param string  $version   The version of the application
     */
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
    }

    /**
     * Sets a container instance onto this application.
     *
     * @param \Pimple $container
     *
     * @return void
     */
    public function setContainer(\Pimple $container)
    {
        $this->container = $container;
    }

    /**
     * Get the Container.
     *
     * @return \Pimple
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Returns a service contained in the application container or null if none is found with that name.
     *
     * This is a convenience method used to retrieve an element from the Application container without having to assign
     * the results of the getContainer() method in every call.
     *
     * @param string $name Name of the service.
     *
     * @see self::getContainer()
     *
     * @api
     *
     * @return mixed|null
     */
    public function getService($name)
    {
        return isset($this->container[$name]) ? $this->container[$name] : null;
    }
}
