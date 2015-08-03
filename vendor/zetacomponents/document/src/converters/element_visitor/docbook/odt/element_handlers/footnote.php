<?php
/**
 * File containing the ezcDocumentDocbookToOdtFootnoteHandler class.
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
 * Visit footnotes.
 *
 * Visit docbook <footnote/> and transform them into ODT <text:note/>.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentDocbookToOdtFootnoteHandler extends ezcDocumentDocbookToOdtBaseHandler
{
    /**
     * Current footnote count.
     * 
     * @var int
     */
    protected $counter = 0;

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
        $label = $node->hasAttribute( 'label' ) ? $node->getAttribute( 'label' ) : ++$this->counter;

        // Adjust counter for inconsequently labeled notes
        if ( ctype_digit( $label ) && $label > $this->counter )
        {
            $this->counter = $label + 1;
        }

        $textNote = $root->ownerDocument->createElementNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:note'
        );
        $textNote->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:id',
            // OOO format
            'ftn' . $label
        );
        $textNote->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:note-class',
            'footnote'
        );

        $noteCitation = $root->ownerDocument->createElementNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:note-citation',
            $label
        );
        $noteCitation->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:label',
            $label
        );
        $textNote->appendChild( $noteCitation );

        $noteBody = $root->ownerDocument->createElementNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:note-body'
        );
        $textNote->appendChild( $noteBody );

        $root->appendChild( $textNote );

        $converter->visitChildren( $node, $noteBody );
        return $root;
    }
}

?>
