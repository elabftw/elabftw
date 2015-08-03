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

namespace JMS\Parser;

/**
 * The simple lexer is a fully usable lexer that does not require sub-classing.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SimpleLexer extends AbstractLexer
{
    private $regex;
    private $callable;
    private $tokenNames;

    public function __construct($regex, array $tokenNames, $typeCallable)
    {
        $this->regex = $regex;
        $this->callable = $typeCallable;
        $this->tokenNames = $tokenNames;
    }

    public function getName($type)
    {
        if ( ! isset($this->tokenNames[$type])) {
            throw new \InvalidArgumentException(sprintf('There is no token with type %s.', json_encode($type)));
        }

        return $this->tokenNames[$type];
    }

    protected function getRegex()
    {
        return $this->regex;
    }

    protected function determineTypeAndValue($value)
    {
        return call_user_func($this->callable, $value);
    }
}