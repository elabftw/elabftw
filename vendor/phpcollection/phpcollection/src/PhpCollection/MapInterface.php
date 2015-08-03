<?php

/*
 * Copyright 2012 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PhpCollection;

use PhpOption\Option;

/**
 * Basic map interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface MapInterface extends CollectionInterface
{
    /**
     * Returns the first element in the collection if available.
     *
     * @return Option on array<K,V>
     */
    public function first();

    /**
     * Returns the last element in the collection if available.
     *
     * @return Option on array<K,V>
     */
    public function last();

    /**
     * Searches the collection for an element.
     *
     * @param callable $callable receives the element as first argument, and returns true, or false
     *
     * @return Option on array<K,V>
     */
    public function find($callable);

    /**
     * Returns the value associated with the given key.
     *
     * @param mixed $key
     *
     * @return Option on V
     */
    public function get($key);

    /**
     * Returns whether this map contains a given key.
     *
     * @param mixed $key
     *
     * @return boolean
     */
    public function containsKey($key);

    /**
     * Puts a new element in the map.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * Removes an element from the map.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function remove($key);

    /**
     * Adds all another map to this map, and returns itself.
     *
     * @param MapInterface $map
     *
     * @return MapInterface
     */
    public function addMap(MapInterface $map);

    /**
     * Returns an array with the keys.
     *
     * @return array
     */
    public function keys();

    /**
     * Returns an array with the values.
     *
     * @return array
     */
    public function values();

    /**
     * Returns a new sequence by omitting the given number of elements from the beginning.
     *
     * If the passed number is greater than the available number of elements, all will be removed.
     *
     * @param integer $number
     *
     * @return MapInterface
     */
    public function drop($number);

    /**
     * Returns a new sequence by omitting the given number of elements from the end.
     *
     * If the passed number is greater than the available number of elements, all will be removed.
     *
     * @param integer $number
     *
     * @return MapInterface
     */
    public function dropRight($number);

    /**
     * Returns a new sequence by omitting elements from the beginning for as long as the callable returns true.
     *
     * @param callable $callable Receives the element to drop as first argument, and returns true (drop), or false (stop).
     *
     * @return MapInterface
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
     * @return MapInterface
     */
    public function take($number);

    /**
     * Creates a new collection by taking elements from the current collection
     * for as long as the callable returns true.
     *
     * @param callable $callable
     *
     * @return MapInterface
     */
    public function takeWhile($callable);
}
