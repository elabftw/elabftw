<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex\Provider\Console\Adapter\Silex;

use Cilex\Provider\Console\BaseConsoleServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Silex Console Service Provider adapter
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ConsoleServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $serviceProvider = new BaseConsoleServiceProvider;
        $serviceProvider->register($app);
    }
}
