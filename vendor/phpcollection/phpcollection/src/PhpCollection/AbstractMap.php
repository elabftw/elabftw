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

/**
 * A simple map implementation which basically wraps an array with an object oriented interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AbstractMap extends AbstractCollection implements \IteratorAggregate, MapInterface
{
    protected $elements;

    public function __construct(array $elements = array())
    {
        $this->elements = $elements;
    }

    public function set($key, $value)
    {
        $this->elements[$key] = $value;
    }

    public function exists($callable)
    {
        foreach ($this as $k => $v) {
            if ($callable($k, $v) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets all key/value pairs in the map.
     *
     * @param array $kvMap
     *
     * @return void
     */
    public function setAll(array $kvMap)
    {
        $this->elements = array_merge($this->elements, $kvMap);
    }

    public function addMap(MapInterface $map)
    {
        foreach ($map as $k => $v) {
            $this->elements[$k] = $v;
        }
    }

    public function get($key)
    {
        if (isset($this->elements[$key])) {
            return new Some($this->elements[$key]);
        }

        return None::create();
    }

    public function remove($key)
    {
        if ( ! isset($this->elements[$key])) {
            throw new \InvalidArgumentException(sprintf('The map has no key named "%s".', $key));
        }

        $element = $this->elements[$key];
        unset($this->elements[$key]);

        return $element;
    }

    public function clear()
    {
        $this->elements = array();
    }

    public function first()
    {
        if (empty($this->elements)) {
            return None::create();
        }

        $elem = reset($this->elements);

        return new Some(array(key($this->elements), $elem));
    }

    public function last()
    {
        if (empty($this->elements)) {
            return None::create();
        }

        $elem = end($this->elements);

        return new Some(array(key($this->elements), $elem));
    }

    public function contains($elem)
    {
        foreach ($this->elements as $existingElem) {
            if ($existingElem === $elem) {
                return true;
            }
        }

        return false;
    }

    public function containsKey($key)
    {
        return isset($this->elements[$key]);
    }

    public function isEmpty()
    {
        return empty($this->elements);
    }

    /**
     * Returns a new filtered map.
     *
     * @param callable $callable receives the element and must return true (= keep), or false (= remove).
     *
     * @return AbstractMap
     */
    public function filter($callable)
    {
        return $this->filterInternal($callable, true);
    }

    /**
     * Returns a new filtered map.
     *
     * @param callable $callable receives the element and must return true (= remove), or false (= keep).
     *
     * @return AbstractMap
     */
    public function filterNot($callable)
    {
        return $this->filterInternal($callable, false);
    }

    private function filterInternal($callable, $booleanKeep)
    {
        $newElements = array();
        foreach ($this->elements as $k => $element) {
            if ($booleanKeep !== call_user_func($callable, $element)) {
                continue;
            }

            $newElements[$k] = $element;
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

    public function dropWhile($callable)
    {
        $newElements = array();
        $stopped = false;
        foreach ($this->elements as $k => $v) {
            if ( ! $stopped) {
                if (call_user_func($callable, $k, $v) === true) {
                    continue;
                }

                $stopped = true;
            }

            $newElements[$k] = $v;
        }

        return $this->createNew($newElements);
    }

    public function drop($number)
    {
        if ($number <= 0) {
            throw new \InvalidArgumentException(sprintf('The number must be greater than 0, but got %d.', $number));
        }

        return $this->createNew(array_slice($this->elements, $number, null, true));
    }

    public function dropRight($number)
    {
        if ($number <= 0) {
            throw new \InvalidArgumentException(sprintf('The number must be greater than 0, but got %d.', $number));
        }

        return $this->createNew(array_slice($this->elements, 0, -1 * $number, true));
    }

    public function take($number)
    {
        if ($number <= 0) {
            throw new \InvalidArgumentException(sprintf('The number must be greater than 0, but got %d.', $number));
        }

        return $this->createNew(array_slice($this->elements, 0, $number, true));
    }

    public function takeWhile($callable)
    {
        $newElements = array();
        foreach ($this->elements as $k => $v) {
            if (call_user_func($callable, $k, $v) !== true) {
                break;
            }

            $newElements[$k] = $v;
        }

        return $this->createNew($newElements);
    }

    public function find($callable)
    {
        foreach ($this->elements as $k => $v) {
            if (call_user_func($callable, $k, $v) === true) {
                return new Some(array($k, $v));
            }
        }

        return None::create();
    }

    public function keys()
    {
        return array_keys($this->elements);
    }

    public function values()
    {
        return array_values($this->elements);
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