<?php

/*
 * This file is part of the Cilex framework.
 *
 * (c) Mike van Riel <mike.vanriel@naenius.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cilex\Tests\Provider;

use Cilex\Application;
use Cilex\Provider\ValidatorServiceProvider;

/**
 * ValidatorServiceProvider.
 *
 * Originally provided with the Silex Framework; test has been adapted for Cilex.
 *
 * @author Javier Lopez <f12loalf@gmail.com>
 * @author Mike van Riel <mike.vanriel@naenius.com>
 */
class ValidatorServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!is_dir(__DIR__.'/../../../../vendor/symfony/validator/Symfony/Component/Validator')) {
            $this->markTestSkipped('Validator submodule was not installed.');
        }
    }

    public function testRegister()
    {
        $app = new Application('Test');

        $app->register(new ValidatorServiceProvider(), array(
            'validator.class_path' =>  __DIR__.'/../../../../vendor/Symfony/Component/Validator'
        ));

        return $app;
    }

    /**
     * @depends testRegister
     */
    public function testValidatorServiceIsAValidator($app)
    {
        $this->assertInstanceOf('Symfony\Component\Validator\Validator', $app['validator']);
    }
}