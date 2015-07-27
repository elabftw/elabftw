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

use PhpOption\Some;
use PhpOption\None;
use PhpOption\Option;
use OutOfBoundsException;

/**
 * A sequence with numerically indexed elements.
 *
 * This is rawly equivalent to an array with only numeric keys.
 * There are no restrictions on how many same values may occur in the sequence.
 *
 * This sequence is mutable.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AbstractSequence extends AbstractCollection implements \IteratorAggregate, SequenceInterface
{
    protected $elements;

    /**
     * @param array $elements
     */
    public function __construct(array $elements = array())
    {
        $this->elements = array_values($elements);
    }

    public function addSequence(SequenceInterface $seq)
    {
        $this->addAll($seq->all());
    }

    public function indexOf($searchedElement)
    {
        foreach ($this->elements as $i => $element) {
            if ($searchedElement === $element) {
                return $i;
            }
        }

        return -1;
    }

    public function lastIndexOf($searchedElement)
    {
        for ($i=count($this->elements)-1; $i>=0; $i--) {
            if ($this->elements[$i] === $searchedElement) {
                return $i;
            }
        }

        return -1;
    }

    public function isDefinedAt($index)
    {
        return isset($this->elements[$index]);
    }

    /**
     * Returns a filtered sequence.
     *
     * @param callable $callable receives the element and must return true (= keep) or false (= remove).
     *
     * @return AbstractSequence
     */
    public function filter($callable)
    {
        return $this->filterInternal($callable, true);
    }

    public function map($callable)
    {
        $newElements = array();
        foreach ($this->elements as $i => $element) {
            $newElements[$i] = $callable($element);
        }

        return $this->createNew($newElements);
    }

    /**
     * Returns a filtered sequence.
     *
     * @param callable $callable receives the element and must return true (= remove) or false (= keep).
     *
     * @return AbstractSequence
     */
    public function filterNot($callable)
    {
        return $this->filterInternal($callable, false);
    }

    private function filterInternal($callable, $booleanKeep)
    {
        $newElements = array();
        foreach ($this->elements as $element) {
            if ($booleanKeep !== call_user_func($callable, $element)) {
                continue;
            }

            $newElements[] = $element;
        }

        return $this->createNew($newElements);
    }

    public function foldLeft($initialValue, $callable)
    {
        $value = $initialValue;
        foreach ($this->elements as $elem) {
            $value = call_user_func($callable, $value, $elem);
        }

        return $value;
    }

    public function foldRight($initialValue, $callable)
    {
        $value = $initialValue;
        foreach (array_reverse($this->elements) as $elem) {
            $value = call_user_func($callable, $elem, $value);
        }

        return $value;
    }

    /**
     * Finds the first index where the given callable returns true.
     *
     * @param callable $callable
     *
     * @return integer the index, or -1 if the predicate is not true for any element.
     */
    public function indexWhere($callable)
    {
        foreach ($this->elements as $i => $element) {
            if (call_user_func($callable, $element) === true) {
                return $i;
            }
        }

        return -1;
    }

    public function lastIndexWhere($callable)
    {
        for ($i=count($this->elements)-1; $i>=0; $i--) {
            if (call_user_func($callable, $this->elements[$i]) === true) {
                return $i;
            }
        }

        return -1;
    }

    public function last()
    {
        if (empty($this->elements)) {
            return None::create();
        }

        return new Some(end($this->elements));
    }

    public function first()
    {
        if (empty($this->elements)) {
            return None::create();
        }

        return new Some(reset($this->elements));
    }

    public function indices()
    {
        return array_keys($this->elements);
    }

    /**
     * Returns an element based on its index (0-based).
     *
     * @param integer $index
     *
     * @return T
     */
    public function get($index)
    {
        if ( ! isset($this->elements[$index])) {
            throw new OutOfBoundsException(sprintf('The index "%s" does not exist in this sequence.', $index));
        }

        return $this->elements[$index];
    }

    /**
     * Removes the element at the given index, and returns it.
     *
     * @param int $index
     *
     * @return T
     *
     * @throws \OutOfBoundsException If there is no element at the given index.
     */
    public function remove($index)
    {
        if ( ! isset($this->elements[$index])) {
            throw new OutOfBoundsException(sprintf('The index "%d" is not in the interval [0, %d).', $index, count($this->elements)));
        }

        $element = $this->elements[$index];
        unset($this->elements[$index]);
        $this->elements = array_values($this->elements);

        return $element;
    }

    /**
     * Updates the element at the given index (0-based).
     *
     * @param integer $index
     * @param T $value
     */
    public function update($index, $value)
    {
        if ( ! isset($this->elements[$index])) {
            throw new \InvalidArgumentException(sprintf('There is no element at index "%d".', $index));
        }

        $this->elements[$index] = $value;
    }

    public function isEmpty()
    {
        return empty($this->elements);
    }

    public function all()
    {
        return $this->elements;
    }

    public function add($newElement)
    {
        $this->elements[] = $newElement;
    }

    public function addAll(array $addedElements)
    {
        foreach ($addedElements as $newElement) {
            $this->elements[] = $newElement;
        }
    }

    public function take($number)
    {
        if ($number <= 0) {
            throw new \InvalidArgumentException(sprintf('$number must be greater than 0, but got %d.', $number));
        }

        return $this->createNew(array_slice($this->elements, 0, $number));
    }

    /**
     * Extracts element from the head while the passed callable returns true.
     *
     * @param callable $callable receives elements of this sequence as first argument, and returns true/false.
     *
     * @return Sequence
     */
    public function takeWhile($callable)
    {
        $newElements = array();

        for ($i=0,$c=count($this->elements); $i<$c; $i++) {
            if (call_user_func($callable, $this->elements[$i]) !== true) {
                break;
            }

            $newElements[] = $this->elements[$i];
        }

        return $this->createNew($newElements);
    }

    public function drop($number)
    {
        if ($number <= 0) {
            throw new \InvalidArgumentException(sprintf('The number must be greater than 0, but got %d.', $number));
        }

        return $this->createNew(array_slice($this->elements, $number));
    }

    public function dropRight($number)
    {
        if ($number <= 0) {
            throw new \InvalidArgumentException(sprintf('The number must be greater than 0, but got %d.', $number));
        }

        return $this->createNew(array_slice($this->elements, 0, -1 * $number));
    }

    public function dropWhile($callable)
    {
        for ($i=0,$c=count($this->elements); $i<$c; $i++) {
            if (true !== call_user_func($callable, $this->elements[$i])) {
                break;
            }
        }

        return $this->createNew(array_slice($this->elements, $i));
    }

    public function exists($callable)
    {
        foreach ($this as $elem) {
            if ($callable($elem) === true) {
                return true;
            }
        }

        return false;
    }

    public function count()
    {
        return count($this->elements);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }

    protected function createNew(array $elements)
    {
        return new static($elements);
    }
}