<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex\Command;

use \Symfony\Component\Console;

/**
 * Base class for Cilex commands.
 *
 * @author Mike van Riel <mike.vanriel@naenius.com>
 *
 * @api
 */
abstract class Command extends Console\Command\Command
{
    /**
     * Returns the application container.
     *
     * @return \Cilex\Application
     */
    public function getContainer()
    {
        return $this->getApplication()->getContainer();
    }

    /**
     * Returns a service contained in the application container or null if none
     * is found with that name.
     *
     * This is a convenience method used to retrieve an element from the
     * Application container without having to assign the results of the
     * getContainer() method in every call.
     *
     * @param string $name Name of the service
     *
     * @see self::getContainer()
     *
     * @api
     *
     * @return \stdClass|null
     */
    public function getService($name)
    {
        return $this->getApplication()->getService($name);
    }
}
