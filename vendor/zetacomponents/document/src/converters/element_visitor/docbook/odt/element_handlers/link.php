<?php
/**
 * File containing the ezcDocumentDocbookToOdtLinkHandler class.
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
 * Visit links.
 *
 * Visit docbook <link/> and transform them into ODT <text:reference-ref/>.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentDocbookToOdtLinkHandler extends ezcDocumentDocbookToOdtBaseHandler
{
    /**
     * Handle a node
     *
     * Handle / transform a given node, and return the result of the
     * conversion.
     *
     * @param ezcDocumentElementVisitorConverter $converter
     * @param DOMElement $node
     * @param mixed $root
     * @return mixed
     */
    public function handle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $refRef = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'text:reference-ref',
                $node->nodeValue
            )
        );
        $refRef->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:ref-name',
            $node->getAttribute( 'linked' )
        );

        return $root;
    }
}

?>
