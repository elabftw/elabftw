<?php
/**
 * File containing the ezcDocumentListBulletGuesser class.
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
 * Simple mapping class to guess bullet charachters from mark names.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentListBulletGuesser
{
    /**
     * Mapping of mark names to UTF-8 bullet characters.
     * 
     * @var array(string=>string)
     */
    protected $docBookCharMap = array(
        'circle' => '⚪',
        'circ'   => '⚪',
        'square' => '◼',
        'dics'   => '⚫',
        'skull'  => '☠',
        'smiley' => '☺',
        'arrow'  => '→',
    );

    /**
     * Returns a UTF-8 bullet character for the given $mark.
     *
     * $mark can be a single character, in which case this character is 
     * returned. Otherwise, the given $mark string is tried to be interpreted 
     * and an according UTF-8 char is returned, if found. If this match fails, 
     * the $default is returned.
     * 
     * @param string $mark 
     * @param string $default 
     * @return string
     */
    public function markToChar( $mark, $default = '⚫' )
    {
        if ( iconv_strlen( $mark, 'UTF-8' ) === 1 )
        {
            return $mark;
        }
        $mark = strtolower( $mark );
        if ( isset( $this->docBookCharMap[$mark] ) )
        {
            return $this->docBookCharMap[$mark];
        }
        return $default;
    }
}

?>
