<?php

namespace PhpCollection\ObjectBasicsHandler;

use PhpCollection\ObjectBasicsHandler;

class IdentityHandler implements ObjectBasicsHandler
{
    public function hash($object)
    {
        return spl_object_hash($object);
    }

    public function equals($a, $b)
    {
        return $a === $b;
    }
}