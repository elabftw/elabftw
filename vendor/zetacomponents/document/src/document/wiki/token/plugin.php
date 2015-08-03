<?php
/**
 * File containing the ezcDocumentWikiPluginToken struct
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Document
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Struct for Wiki plugin token.
 *
 * The most complex token, just contains the full plugin contents. May be post
 * process by the tokenizer to extract its type, parameters and text values.
 * Otherwise it will be ignored, and not handled properly by the parser.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentWikiPluginToken extends ezcDocumentWikiBlockMarkupToken
{
    /**
     * Plugin type / name.
     *
     * @var string
     */
    public $type;

    /**
     * Plugin parameters
     *
     * @var array
     */
    public $parameters;

    /**
     * Plugin content
     *
     * @var string
     */
    public $text;

    /**
     * Set state after var_export
     *
     * @param array $properties
     * @return void
     * @ignore
     */
    public static function __set_state( $properties )
    {
        $tokenClass = __CLASS__;
        $token = new $tokenClass(
            $properties['content'],
            $properties['line'],
            $properties['position']
        );

        // Set additional token values
        $token->type       = $properties['type'];
        $token->parameters = $properties['parameters'];
        $token->text       = $properties['text'];

        return $token;
    }
}

?>
