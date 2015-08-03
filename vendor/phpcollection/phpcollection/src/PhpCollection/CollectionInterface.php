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

/**
 * Basic interface which adds some behaviors, and a few methods common to all collections.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface CollectionInterface extends \Traversable, \Countable
{
    /**
     * Returns whether this collection contains the passed element.
     *
     * @param mixed $elem
     *
     * @return boolean
     */
    public function contains($elem);

    /**
     * Returns whether the collection is empty.
     *
     * @return boolean
     */
    public function isEmpty();

    /**
     * Returns a filtered collection of the same type.
     *
     * Removes all elements for which the provided callable returns false.
     *
     * @param callable $callable receives an element of the collection and must return true (= keep) or false (= remove).
     *
     * @return CollectionInterface
     */
    public function filter($callable);

    /**
     * Returns a filtered collection of the same type.
     *
     * Removes all elements for which the provided callable returns true.
     *
     * @param callable $callable receives an element of the collection and must return true (= remove) or false (= keep).
     *
     * @return CollectionInterface
     */
    public function filterNot($callable);

    /**
     * Applies the callable to an initial value and each element, going left to right.
     *
     * @param mixed $initialValue
     * @param callable $callable receives the current value (the first time this equals $initialValue) and the element
     *
     * @return mixed the last value returned by $callable, or $initialValue if collection is empty.
     */
    public function foldLeft($initialValue, $callable);

    /**
     * Applies the callable to each element, and an initial value, going right to left.
     *
     * @param mixed $initialValue
     * @param callable $callable receives the element, and the current value (the first time this equals $initialValue).
     * @return mixed the last value returned by $callable, or $initialValue if collection is empty.
     */
    public function foldRight($initialValue, $callable);
}