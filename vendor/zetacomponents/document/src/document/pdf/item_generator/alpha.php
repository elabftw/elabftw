<?php
/**
 * File containing the ezcDocumentAlphaListItemGenerator class.
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
 * Numbered list item generator
 *
 * Generator for alphabetical list items. Generated list items start with "a" 
 * to "z" and will use more characters for lists with more then 26 list items, 
 * like "ab" for the 28th list item.
 *
 * Basically implements a number recoding to base 26, only using alphabetical 
 * characters.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentAlphaListItemGenerator extends ezcDocumentAlnumListItemGenerator
{
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
        $item = '';
        while ( $number > 0 )
        {
            $item   = chr( $number % 26 + ord( 'a' ) - 1 ) . $item;
            $number = floor( $number / 26 );
        }

        return $this->applyStyle( $item );
    }
}

?>
