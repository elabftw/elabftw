<?php
/**
 * File containing the ezcDocumentEzXmlToDocbookMappingHandler class.
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
 * Simple mapping handler.
 *
 * Special visitor for elements which just need trivial mapping of element
 * tag names. It ignores all attributes of the input element and just
 * converts the tag name.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentEzXmlToDocbookMappingHandler extends ezcDocumentElementVisitorHandler
{
    /**
     * Mapping of element names.
     *
     * Element tag name mapping for elements, which just require trivial
     * mapping used by the visitWithMapper() method.
     *
     * @var array
     */
    protected $mapping = array(
        'section'   => 'section',
        'paragraph' => 'para',
        'li'        => 'listitem',
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
        if ( !isset( $this->mapping[$node->tagName] ) )
        {
            $converter->triggerError( E_WARNING,
                "Mapping handler used for element '{$node->tagName}', not known by the mapping handler."
            );
            return $root;
        }

        $element = $root->ownerDocument->createElement( $this->mapping[$node->tagName] );
        $root->appendChild( $element );

        // Recurse
        $converter->visitChildren( $node, $element );
        return $root;
    }
}

?>
