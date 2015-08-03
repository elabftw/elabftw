<?php
/**
 * File containing the ezcDocumentWikiImageStartToken struct
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
 * Struct for Wiki document image tag open marker tokens
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentWikiImageStartToken extends ezcDocumentWikiInlineMarkupToken
{
    /**
     * Image width
     *
     * @var int
     */
    public $width      = null;

    /**
     * Image height
     *
     * @var int
     */
    public $height     = null;

    /**
     * Image alignement
     *
     * @var string
     */
    public $alignement = null;

    /**
     * Get image parameter order
     *
     * Images may have any amount of parameters and the order may not be the
     * same for each amount. This method should return an ordered list of
     * parameter names for the given amount of parameters.
     *
     * @param int $count
     * @return array
     */
    public function getImageParameterOrder( $count )
    {
        return array_slice(
            array(
                'resource',
                'title',
            ),
            0, $count
        );
    }

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
        $token->width      = $properties['width'];
        $token->height     = $properties['height'];
        $token->alignement = $properties['alignement'];

        return $token;
    }
}

?>
