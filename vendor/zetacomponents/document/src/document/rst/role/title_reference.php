<?php
/**
 * File containing the ezcDocumentRstTitleReferenceTextRole class.
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
 * Visitor for RST title reference text roles.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentRstTitleReferenceTextRole extends ezcDocumentRstTextRole implements ezcDocumentRstXhtmlTextRole
{
    /**
     * Transform text role to docbook.
     *
     * Create a docbook XML structure at the text roles position in the
     * document.
     *
     * @param DOMDocument $document
     * @param DOMElement $root
     */
    public function toDocbook( DOMDocument $document, DOMElement $root )
    {
        $title= $document->createElement( 'citetitle' );
        $root->appendChild( $title );

        $this->appendText( $title );
    }

    /**
     * Transform text role to HTML.
     *
     * Create a XHTML structure at the text roles position in the document.
     *
     * @param DOMDocument $document
     * @param DOMElement $root
     */
    public function toXhtml( DOMDocument $document, DOMElement $root )
    {
        $title= $document->createElement( 'cite' );
        $root->appendChild( $title );

        $this->appendText( $title );
    }
}

?>
