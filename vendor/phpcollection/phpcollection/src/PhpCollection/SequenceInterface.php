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
 * Interface for mutable sequences.
 *
 * Equality of elements in the sequence is established via a shallow comparison (===).
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SequenceInterface extends CollectionInterface
{
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
     * Returns all elements in this sequence.
     *
     * @return array
     */
    public function all();

    /**
     * Adds the elements of another sequence to this sequence.
     *
     * @param SequenceInterface $seq
     *
     * @return SequenceInterface
     */
    public function addSequence(SequenceInterface $seq);

    /**
     * Returns the index of the passed element.
     *
     * @param mixed $elem
     *
     * @return integer the index (0-based), or -1 if not found
     */
    public function indexOf($elem);

    /**
     * Returns the last index of the passed element.
     *
     * @param mixed $elem
     * @return integer the index (0-based), or -1 if not found
     */
    public function lastIndexOf($elem);

    /**
     * Returns whether the given index is defined in the sequence.
     *
     * @param integer $index (0-based)
     * @return boolean
     */
    public function isDefinedAt($index);

    /**
     * Returns the first index where the given callable returns true.
     *
     * @param callable $callable receives the element as first argument, and returns true, or false
     *
     * @return integer the index (0-based), or -1 if the callable returns false for all elements
     */
    public function indexWhere($callable);

    /**
     * Returns the last index where the given callable returns true.
     *
     * @param callable $callable receives the element as first argument, and returns true, or false
     *
     * @return integer the index (0-based), or -1 if the callable returns false for all elements
     */
    public function lastIndexWhere($callable);

    /**
     * Returns all indices of this collection.
     *
     * @return integer[]
     */
    public function indices();

    /**
     * Returns the element at the given index.
     *
     * @param integer $index (0-based)
     *
     * @return mixed
     */
    public function get($index);

    /**
     * Adds an element to the sequence.
     *
     * @param mixed $elem
     *
     * @return void
     */
    public function add($elem);

    /**
     * Removes the element at the given index, and returns it.
     *
     * @param integer $index
     *
     * @return mixed
     */
    public function remove($index);

    /**
     * Adds all elements to the sequence.
     *
     * @param array $elements
     *
     * @return void
     */
    public function addAll(array $elements);

    /**
     * Updates the value at the given index.
     *
     * @param integer $index
     * @param mixed $value
     *
     * @return void
     */
    public function update($index, $value);

    /**
     * Returns a new sequence by omitting the given number of elements from the beginning.
     *
     * If the passed number is greater than the available number of elements, all will be removed.
     *
     * @param integer $number
     *
     * @return SequenceInterface
     */
    public function drop($number);

    /**
     * Returns a new sequence by omitting the given number of elements from the end.
     *
     * If the passed number is greater than the available number of elements, all will be removed.
     *
     * @param integer $number
     *
     * @return SequenceInterface
     */
    public function dropRight($number);

    /**
     * Returns a new sequence by omitting elements from the beginning for as long as the callable returns true.
     *
     * @param callable $callable Receives the element to drop as first argument, and returns true (drop), or false (stop).
     *
     * @return SequenceInterface
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
