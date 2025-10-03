<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use ReflectionClass;

trait ControllerUtilsTrait
{
    /**
     * @template T of object
     * @param class-string<T>|T $class
     * @return T
     */
    protected function makeWithoutConstructor($class): object
    {
        $ref = new ReflectionClass($class); // accepts object or class-string
        return $ref->newInstanceWithoutConstructor();
    }

    /**
     * @template T of object
     * @param T $class
     * @return mixed
     */
    protected function invokeProtected($class, string $method): mixed
    {
        $ref = new ReflectionClass($class);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($class);
    }
}
