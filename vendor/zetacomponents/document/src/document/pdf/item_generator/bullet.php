<?php
/**
 * File containing the ezcDocumentBulletListItemGenerator class.
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
 * @access private
 */

/**
 * List item generator.
 *
 * Simple bullet list item generator, which returns the character passed to the 
 * constructor for each list item.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentBulletListItemGenerator extends ezcDocumentListItemGenerator
{
    /**
     * Character used for the bullet lsit items
     * 
     * @var string
     */
    protected $character;

    /**
     * Construct from bullet to use
     * 
     * @param string $char 
     * @return void
     */
    public function __construct( $char = '-' )
    {
        $this->character = $char;
    }

    /**
     * Get list item
     *
     * Get the n-th list item. The index of the list item is specified by the
     * number parameter.
     * 
     * @param int $number 
     * @return string
     */
    public function getListItem( $number )
    {
        return $this->character;
    }
}

?>
