<?php

namespace PhpCollection;
use PhpOption\Option;

/**
 * Interface for sets.
 *
 * Each Set contains equal values only once.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SetInterface extends CollectionInterface, \IteratorAggregate
{
    /**
     * @param object|scalar $elem
     * @return void
     */
    public function add($elem);

    /**
     * @param object|scalar $elements
     * @return void
     */
    public function addAll(array $elements);

    /**
     * @param object|scalar $elem
     * @return void
     */
    public function remove($elem);

    /**
     * Returns the first element in the collection if available.
     *
     * @return Option
     */
    public function first();

    /**
     * Returns the last element in the collection if available.
     *
     * @return Option
     */
    public function last();

    /**
     * Returns all elements in this Set.
     *
     * @return array
     */
    public function all();

    /**
     * Returns a new Set with all elements in reverse order.
     *
     * @return SetInterface
     */
    public function reverse();

    /**
     * Adds the elements of another Set to this Set.
     *
     * @param SetInterface $seq
     *
     * @return SetInterface
     */
    public function addSet(SetInterface $seq);

    /**
     * Returns a new Set by omitting the given number of elements from the beginning.
     *
     * If the passed number is greater than the available number of elements, all will be removed.
     *
     * @param integer $number
     *
     * @return SetInterface
     */
    public function drop($number);

    /**
     * Returns a new Set by omitting the given number of elements from the end.
     *
     * If the passed number is greater than the available number of elements, all will be removed.
     *
     * @param integer $number
     *
     * @return SetInterface
     */
    public function dropRight($number);

    /**
     * Returns a new Set by omitting elements from the beginning for as long as the callable returns true.
     *
     * @param callable $callable Receives the element to drop as first argument, and returns true (drop), or false (stop).
     *
     * @return SetInterface
     */
    public function dropWhile($callable);

    /**
     * Creates a new collection by taking the given number of elements from the beginning
     * of the current collection.
     *
     * If the passed number is greater than the available number of elements, then all elements
     * will be returned as a new collection.
     *
     * @param integer $number
     *
     * @return CollectionInterface
     */
    public function take($number);

    /**
     * Creates a new collection by taking elements from the current collection
     * for as long as the callable returns true.
     *
     * @param callable $callable
     *
     * @return CollectionInterface
     */
    public function takeWhile($callable);

    /**
     * Creates a new collection by applying the passed callable to all elements
     * of the current collection.
     *
     * @param callable $callable
     * @return CollectionInterface
     */
    public function map($callable);
}