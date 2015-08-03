<?php
/**
 * File containing the ezcDocumentOdtImageFilter class.
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
 * Filter which extracts images from FODT (flat ODT) documents and stores them 
 * in the desired directory.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtImageFilter extends ezcDocumentOdtBaseFilter
{
    /**
     * ODT document options. 
     * 
     * @var ezcDocumentOdtOptions
     */
    protected $options;

    /**
     * Creates the filter object.
     *
     * Creates the filter object. Makes use of $imageDirectory, defined in the 
     * $options.
     *
     * @param ezcDocumentOdtOptions $options
     * @return void
     */
    public function __construct( ezcDocumentOdtOptions $options )
    {
        $this->options = $options;
    }

    /**
     * Filter ODT document.
     *
     * Filter for the document, which may modify / restructure a document and
     * assign semantic information bits to the elements in the tree.
     *
     * @param DOMDocument $document
     * @return DOMDocument
     */
    public function filter( DOMDocument $document )
    {
        $xpath = new DOMXPath( $document );

        $xpath->registerNamespace( 'office', ezcDocumentOdt::NS_ODT_OFFICE );
        $xpath->registerNamespace( 'draw', ezcDocumentOdt::NS_ODT_DRAWING );

        $binaries = $xpath->query( '//draw:image/office:binary-data' );
        
        foreach ( $binaries as $binary )
        {
            $this->extractBinary( $binary );
        }
        return $document;
    }

    /**
     * Extracts the binary content from $binary into a file.
     *
     * Extracts the binary image content from $binary to a file in the image 
     * directory ({@link $options}). The file name is created using {@link tempnam()} 
     * and set as an XLink HREF on the parent <draw:image/> element, as it 
     * would typically be in an ODT.
     *
     * @param DOMElement $binary
     */
    protected function extractBinary( DOMElement $binary )
    {
        $fileName = tempnam( $this->options->imageDir, 'ezcDocumentOdt' );
        
        file_put_contents(
            $fileName,
            base64_decode( $binary->nodeValue )
        );

        $binary->parentNode->setAttributeNS(
            ezcDocumentOdt::NS_XLINK,
            'xlink:href',
            $fileName
        );
    }
}

?>
