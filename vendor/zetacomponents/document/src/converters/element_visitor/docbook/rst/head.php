<?php
/**
 * File containing the ezcDocumentDocbookToRstHeadHandler class.
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
 * Visit docbook sectioninfo elements
 *
 * The sectioninfo elements contain metadata about the document or
 * sections, which are transformed into the respective metadata in the HTML
 * header.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToRstHeadHandler extends ezcDocumentDocbookToRstBaseHandler
{
    /**
     * Element name mapping for meta information in the docbook document to
     * HTML meta element names.
     *
     * @var array
     */
    protected $headerMapping = array(
        'authors'     => 'Authors',
        'abstract'    => 'Description',
        'copyright'   => 'Copyright',
        'releaseinfo' => 'Version',
        'pubdate'     => 'Date',
        'date'        => 'Date',
        'author'      => 'Author',
        'publisher'   => 'Author',
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
        foreach ( $this->headerMapping as $tagName => $metaName )
        {
            if ( ( $nodes = $node->getElementsBytagName( $tagName ) ) &&
                 ( $nodes->length > 0 ) )
            {
                foreach ( $nodes as $child )
                {
                    $root .= ":$metaName:\n";
                    $root .= ezcDocumentDocbookToRstConverter::wordWrap( trim( $converter->visitChildren( $child, '' ) ), 2 );
                    $root .= "\n";
                }
            }
        }

        return $root;
    }
}

?>
