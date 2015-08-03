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

class SyntaxErrorException extends \RuntimeException
{
    private $actualToken;
    private $context;

    public function setActualToken(array $actualToken)
    {
        $this->actualToken = $actualToken;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getActualToken()
    {
        return $this->actualToken;
    }

    public function getContext()
    {
        return $this->context;
    }
}
