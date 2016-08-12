<?php

namespace PhpCollection\ObjectBasicsHandler;

use PhpCollection\ObjectBasicsHandler;

class DateTimeHandler implements ObjectBasicsHandler
{
    public function hash($object)
    {
        if ( ! $object instanceof \DateTime) {
            throw new \LogicException('$object must be an instance of \DateTime.');
        }

        return $object->getTimestamp();
    }

    public function equals($thisObject, $otherObject)
    {
        if ( ! $thisObject instanceof \DateTime) {
            throw new \LogicException('$thisObject must be an instance of \DateTime.');
        }
        if ( ! $otherObject instanceof \DateTime) {
            return false;
        }

        return $thisObject->format(\DateTime::ISO8601) === $otherObject->format(\DateTime::ISO8601);
    }
}