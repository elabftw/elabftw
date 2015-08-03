<?php
/**
 * File containing the abstrac ezcDocumentDocbookToHtmlBaseHandler base class.
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
 * Basic converter which stores a list of handlers for each node in the docbook
 * element tree. Those handlers will be executed for the elements, when found.
 * The handler can then handle the repective subtree.
 *
 * Additional handlers may be added by the user to the converter class.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentDocbookToHtmlBaseHandler extends ezcDocumentElementVisitorHandler
{
    /**
     * Reference to HTML head element
     *
     * @var DOMElement
     */
    private $head = null;

    /**
     * Get head of HTML document
     *
     * Get the root node of the HTML document head
     *
     * @param DOMElement $element
     * @return DOMElement
     */
    protected function getHead( DOMElement $element )
    {
        if ( $this->head === null )
        {
            // Get reference to head node in destination document
            $xpath = new DOMXPath( $element->ownerDocument );
            $this->head = $xpath->query( '/*[local-name() = "html"]/*[local-name() = "head"]' )->item( 0 );
        }

        return $this->head;
    }
}

?>
