<?php

namespace PhpCollection;

/**
 * Interface that must be implemented by objects that are used as keys, or in sets.
 *
 * For entities, you can use the "EntityLikeObject" trait.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface ObjectBasics
{
    /**
     * Produces a hash for the given object.
     *
     * If two objects are equal (as per the equals() method), the hash() method must produce
     * the same hash for them.
     *
     * The reverse can, but does not necessarily have to be true. That is, if two objects have the
     * same hash, they do not necessarily have to be equal, but the equals() method must be called
     * to be sure.
     *
     * When implementing this method try to use a simple and fast algorithm that produces reasonably
     * different results for non-equal objects, and shift the heavy comparison logic to equals().
     *
     * @return string|integer
     */
    public function hash();

    /**
     * Whether two objects are equal.
     *
     * This can compare by referential equality (===), or in case of value objects like (\DateTime) compare
     * the individual properties of the objects; it's up to the implementation.
     *
     * @return boolean
     */
    public function equals(ObjectBasics $other);
}