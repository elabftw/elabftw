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

/**
 * Interface that must implement all Cilex service providers.
 *
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
interface ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app);
}
