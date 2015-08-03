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

use PhpOption\LazyOption;
use PhpOption\Some;
use PhpOption\None;

abstract class AbstractCollection
{
    public function contains($searchedElem)
    {
        foreach ($this as $elem) {
            if ($elem === $searchedElem) {
                return true;
            }
        }

        return false;
    }

    public function find($callable)
    {
        $self = $this;

        return new LazyOption(function() use ($callable, $self) {
            foreach ($self as $elem) {
                if (call_user_func($callable, $elem) === true) {
                    return new Some($elem);
                }
            }

            return None::create();
        });
    }
}