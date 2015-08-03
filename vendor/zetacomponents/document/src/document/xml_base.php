<?php
/**
 * File containing the ezcDocumentXmlBase class
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
 * A base class for XML based document type handlers.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentXmlBase extends ezcDocument implements ezcDocumentValidation
{
    /**
     * DOM tree as the internal representation for the loaded XML.
     *
     * @var DOMDocument
     */
    protected $document;

    /**
     * Create document from input string
     *
     * Create a document of the current type handler class and parse it into a
     * usable internal structure.
     *
     * @param string $string
     * @return void
     */
    public function loadString( $string )
    {
        // Use internal error handling to handle XML errors manually.
        $oldXmlErrorHandling = libxml_use_internal_errors( true );
        libxml_clear_errors();

        // Load XML document
        $this->document = new DOMDocument();

        // Check if we should format the document later
        if ( $this->options->indentXml )
        {
            $this->document->preserveWhiteSpace = false;
            $this->document->formatOutput = true;
        }

        $this->document->loadXml( $string );

        $errors = ( $this->options->failOnError ?
            libxml_get_errors() :
            null );

        libxml_clear_errors();
        libxml_use_internal_errors( $oldXmlErrorHandling );

        // If there are errors and the error handling is activated throw an
        // exception with the occured errors.
        if ( $errors )
        {
            throw new ezcDocumentErroneousXmlException( $errors );
        }
    }

    /**
     * Construct directly from DOMDocument
     *
     * To save execution time this method offers the construction of XML
     * documents directly from a DOM document instance.
     *
     * @param DOMDocument $document
     * @return void
     */
    public function loadDomDocument( DOMDocument $document )
    {
        $this->document = $document;
    }

    /**
     * Set DOMDocument
     *
     * Directly set the internally stored DOMDocument object, to spare
     * additional XML parsing overhead. Setting a broken or invalid docbook
     * document is not checked here, ebcause validation would cost too much
     * performace on each set. Be careful what you set here, invalid documents
     * may lead to unpredictable errors.
     *
     * @param DOMDocument $document
     * @return void
     */
    public function setDomDocument( DOMDocument $document )
    {
        $this->document = $document;
    }

    /**
     * Get DOMDocument
     *
     * Directly return the internally stored DOMDocument object, to spare
     * additional XML parsing overhead.
     *
     * @return DOMDocument
     */
    public function getDomDocument()
    {
        return $this->document;
    }

    /**
     * Return document as string
     *
     * Serialize the document to a string an return it.
     *
     * @return string
     */
    public function save()
    {
        return $this->document->saveXml( $this->document, LIBXML_NOEMPTYTAG );
    }
}

?>
