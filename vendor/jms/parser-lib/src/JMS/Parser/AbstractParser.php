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
 * Base Parser which provides some useful parsing methods intended for sub-classing.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class AbstractParser
{
    protected $lexer;
    protected $context;

    public function __construct(AbstractLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * Parses the given input.
     *
     * @param string $str
     * @param string $context parsing context (allows to produce better error messages)
     *
     * @return mixed
     */
    public function parse($str, $context = null)
    {
        $this->lexer->setInput($str);
        $this->context = $context;

        $rs = $this->parseInternal();

        if (null !== $this->lexer->next) {
            $this->syntaxError('end of input');
        }

        return $rs;
    }

    /**
     * @return mixed
     */
    abstract protected function parseInternal();

    /**
     * Matches a token, and returns its value.
     *
     * @param integer $type
     *
     * @return mixed the value of the matched token
     */
    protected function match($type)
    {
        if ( ! $this->lexer->isNext($type)) {
            $this->syntaxError($this->lexer->getName($type));
        }

        $this->lexer->moveNext();

        return $this->lexer->token[0];
    }

    /**
     * Matches any of the passed tokens, and returns the matched token's value.
     *
     * @param array<integer> $types
     *
     * @return mixed
     */
    protected function matchAny(array $types)
    {
        if ( ! $this->lexer->isNextAny($types)) {
            $this->syntaxError('any of '.implode(' or ', array_map(array($this->lexer, 'getName'), $types)));
        }

        $this->lexer->moveNext();

        return $this->lexer->token[0];
    }

    /**
     * Raises a syntax error exception.
     *
     * @param string $expectedDesc A human understandable explanation what was expected
     * @param array $actualToken The token that was found. If not given, next token will be assumed.
     */
    protected function syntaxError($expectedDesc, $actualToken = null)
    {
        if (null === $actualToken) {
            $actualToken = $this->lexer->next;
        }
        if (null === $actualToken) {
            $actualDesc = 'end of input';
        } else if ($actualToken[1] === 0) {
            $actualDesc = sprintf('"%s" of type %s at beginning of input', $actualToken[0], $this->lexer->getName($actualToken[2]));
        } else {
            $actualDesc = sprintf('"%s" of type %s at position %d (0-based)', $actualToken[0], $this->lexer->getName($actualToken[2]), $actualToken[1]);
        }

        $ex = new SyntaxErrorException(sprintf('Expected %s, but got %s%s.', $expectedDesc, $actualDesc, $this->context ? ' '.$this->context : ''));
        if (null !== $actualToken) {
            $ex->setActualToken($actualToken);
        }
        if (null !== $this->context) {
            $ex->setContext($this->context);
        }

        throw $ex;
    }
}