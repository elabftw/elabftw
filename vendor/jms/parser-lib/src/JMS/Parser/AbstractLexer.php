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
 * Abstract Lexer.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractLexer
{
    public $token;
    public $next;

    private $i;
    private $peek;
    private $tokens;

    /**
     * Returns the name of the given token.
     *
     * @param integer $type
     *
     * @return string
     */
    public function getName($type)
    {
        $ref = new \ReflectionClass($this);
        foreach ($ref->getConstants() as $name => $value) {
            if ($value === $type) {
                return $name;
            }
        }

        throw new \InvalidArgumentException(sprintf('There is no token with value %s.', json_encode($type)));
    }

    public function setInput($str)
    {
        $tokens = preg_split($this->getRegex(), $str, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        $this->tokens = array();
        foreach ($tokens as $token) {
            list($token[2], $token[0]) = $this->determineTypeAndValue($token[0]);
            $this->tokens[] = $token;
        }

        $this->reset();
    }

    public function reset()
    {
        $this->i = -1;
        $this->peek = 0;
        $this->token = $this->next = null;
        $this->moveNext();
    }

    /**
     * Moves the pointer one token forward.
     *
     * @return boolean if we have not yet reached the end of the input
     */
    public function moveNext()
    {
        $this->peek = 0;
        $this->token = $this->next;
        $this->next = isset($this->tokens[++$this->i]) ? $this->tokens[$this->i] : null;

        return null !== $this->next;
    }

    /**
     * Skips the token stream until a token of the given type.
     *
     * @param integer $type
     *
     * @return boolean true if a token of the passed type was found, false otherwise.
     */
    public function skipUntil($type)
    {
        while ( ! $this->isNext($type) && $this->moveNext());

        if ( ! $this->isNext($type)) {
            throw new \RuntimeException(sprintf('Could not find the token %s.', $this->getName($type)));
        }
    }

    /**
     * @param integer $type
     *
     * @return boolean
     */
    public function isNext($type)
    {
        return null !== $this->next && $type === $this->next[2];
    }

    /**
     * @param array<integer> $types
     *
     * @return boolean
     */
    public function isNextAny(array $types)
    {
        if (null === $this->next) {
            return false;
        }

        foreach ($types as $type) {
            if ($type === $this->next[2]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \PhpOption\Option<[string,integer,integer]>
     */
    public function peek()
    {
        if ( ! isset($this->tokens[$this->i + (++$this->peek)])) {
            return \PhpOption\None::create();
        }

        return new \PhpOption\Some($this->tokens[$this->i + $this->peek]);
    }

    /**
     * @return string
     */
    abstract protected function getRegex();

    /**
     * Determines the type of the given value.
     *
     * This method may also modify the passed value for example to cast them to
     * a different PHP type where necessary.
     *
     * @param string $value
     *
     * @return array a tupel of type and normalized value
     */
    abstract protected function determineTypeAndValue($value);
}
