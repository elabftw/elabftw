<?php
/**
 * File containing the ezcDocumentListItemGenerator class
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
 * List item generator
 *
 * Generator for list items, like bullet list items, and more important,
 * enumerated lists.
 *
 * Intended to return a list item, which is most likely a single character, 
 * based on the passed number. The list item generator implementation is 
 * choosen in the list renderer, depending on the properties of the element to 
 * render.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
abstract class ezcDocumentListItemGenerator
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
    abstract public function getListItem( $number );
}

?>
