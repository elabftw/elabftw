<?php
/**
 * File containing the abstract ezcDocumentXsltConverter base class.
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
 * Base class for conversions between XML documents using XSLT.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentXsltConverter extends ezcDocumentConverter
{
    /**
     * XSLT processor created from the defined XSLT file.
     *
     * @var XSLTProcessor
     */
    protected $xsltProcessor = null;

    /**
     * Construct converter
     *
     * Construct converter from XSLT file, which is used for the actual
     * conversion.
     *
     * @param ezcDocumentXsltConverterOptions $options
     * @return void
     */
    public function __construct( ezcDocumentXsltConverterOptions $options = null )
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'xsl' ) )
        {
            throw new ezcBaseExtensionNotFoundException( 'xsl' );
        }

        parent::__construct(
            $options === null ?
                new ezcDocumentXsltConverterOptions() :
                $options
        );
    }

    /**
     * Convert documents between two formats
     *
     * Convert documents of the given type to the requested type.
     *
     * @param ezcDocumentXmlBase $doc
     * @return ezcDocumentXmlBase
     */
    public function convert( $doc )
    {
        // Create XSLT processor, if not yet initialized
        if ( $this->xsltProcessor === null )
        {
            $stylesheet = new DOMDocument();
            $stylesheet->load( $this->options->xslt );

            $this->xsltProcessor = new XSLTProcessor();
            $this->xsltProcessor->importStyleSheet( $stylesheet );
        }

        // Set provided parameters.
        foreach ( $this->options->parameters as $namespace => $parameters )
        {
            foreach ( $parameters as $option => $value )
            {
                $this->xsltProcessor->setParameter( $namespace, $option, $value );
            }
        }

        // We want to handle the occured errors ourselves.
        $oldErrorHandling = libxml_use_internal_errors( true );

        // Transform input document
        $dom = $this->xsltProcessor->transformToDoc( $doc->getDomDocument() );

        $errors = ( $this->options->failOnError ?
            libxml_get_errors() :
            null );

        libxml_clear_errors();
        libxml_use_internal_errors( $oldErrorHandling );

        // If there are errors and the error handling is activated throw an
        // exception with the occured errors.
        if ( $errors )
        {
            throw new ezcDocumentErroneousXmlException( $errors );
        }

        // Reset parameters, so they are not automatically applied to the next
        // traansformation.
        foreach ( $this->options->parameters as $namespace => $parameters )
        {
            foreach ( $parameters as $option => $value )
            {
                $this->xsltProcessor->removeParameter( $namespace, $option );
            }
        }

        // Build document from transformation and return that.
        return $this->buildDocument( $dom );
    }

    /**
     * Build document
     *
     * Build document of appropriate type from the DOMDocument, created by the
     * XSLT transformation.
     *
     * @param DOMDocument $document
     * @return ezcDocumentXmlBase
     */
    abstract protected function buildDocument( DOMDocument $document );
}

?>
