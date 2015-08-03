<?php
/**
 * File containing the ezcDocumentDocbookToOdtMappingHandler class.
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
 * Simple mapping handler
 *
 * Performs a simple 1 to 1 mapping between DocBook elements and ODT elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentDocbookToOdtMappingHandler extends ezcDocumentDocbookToOdtBaseHandler
{
    /**
     * Mapping of element names.
     *
     * Mapping from DocBook to ODT elements. The local name of a DocBook 
     * element is used as the key to look up a corresponding element in ODT.  
     * Since ODT utilizes multiple namespaces, an array of namespace and local 
     * name for the target element is returned.
     *
     * @var array(string=>array(string))
     */
    protected $mapping = array(
        'listitem' => array( ezcDocumentOdt::NS_ODT_TEXT, 'text:list-item' )
    );

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
        if ( !isset( $this->mapping[$node->localName] ) )
        {
            // This only occurs if the mapper is assigned to an unknown 
            // element, which should not happen at all.
            throw new ezcDocumentMissingVisitorException(
                $node->localName
            );
        }

        $targetElementData = $this->mapping[$node->localName];

        $targetElement = $root->appendChild(
            $root->ownerDocument->createElementNS(
                $targetElementData[0],
                $targetElementData[1]
            )
        );

        $this->styler->applyStyles( $node, $targetElement );

        $converter->visitChildren( $node, $targetElement );
        return $root;
    }
}

?>
