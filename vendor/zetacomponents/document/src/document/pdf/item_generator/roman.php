<?php
/**
 * File containing the ezcDocumentRomanListItemGenerator class.
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
 * Roman number list item generator.
 *
 * Generator for roman numbered list items. Basically converts the list item 
 * number into a roman number and returns that. Roman numbering is only 
 * properly support up to numbers of about 1000. Lists with more items will 
 * generate strange to read numbers, because they can only be represented using 
 * lots of repetitions of the "M" representing 1000.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentRomanListItemGenerator extends ezcDocumentAlnumListItemGenerator
{
    /**
     * Number map.
     * 
     * @var array(int=>string)
     */
    protected $numbers = array(
        1000 => 'M',
        900  => 'CM',
        500  => 'D',
        400  => 'CD',
        100  => 'C',
        90   => 'XC',
        50   => 'L',
        40   => 'XL',
        10   => 'X',
        9    => 'IX',
        5    => 'V',
        4    => 'IV',
        1    => 'I',
    );

    /**
     * Get list item.
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
        foreach ( $this->numbers as $value => $char )
        {
            while ( $number >= $value )
            {
                $item   .= $char;
                $number -= $value;
            }
        }

        return $this->applyStyle( $item );
    }
}

?>
