<?php
/**
 * File containing the ezcDocumentEzXmlToDocbookConverter class.
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
 * Converter for docbook to XDocbook with a PHP callback based mechanism, for fast
 * and easy PHP based extensible transformations.
 *
 * This converter does not support the full docbook standard, but only a subset
 * commonly used in the document component. If you need to transform documents
 * using the full docbook you might prefer to use the
 * ezcDocumentEzXmlToDocbookXsltConverter with the default stylesheet from
 * Welsh.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentEzXmlToDocbookConverter extends ezcDocumentElementVisitorConverter
{
    /**
     * Deafult document namespace.
     *
     * If no namespace has been explicitely declared in the source document
     * assume this as the defalt namespace.
     *
     * @var string
     */
    protected $defaultNamespace = 'ezxml';

    /**
     * Construct converter
     *
     * Construct converter from XSLT file, which is used for the actual
     *
     * @param ezcDocumentEzXmlToDocbookConverterOptions $options
     * @return void
     */
    public function __construct( ezcDocumentEzXmlToDocbookConverterOptions $options = null )
    {
        parent::__construct(
            $options === null ?
                new ezcDocumentEzXmlToDocbookConverterOptions() :
                $options
        );

        // Initlize common element handlers
        $this->visitorElementHandler = array(
            'ezxml' => array(
                'section'          => $mapper = new ezcDocumentEzXmlToDocbookMappingHandler(),
                'header'           => new ezcDocumentEzXmlToDocbookHeaderHandler(),
                'paragraph'        => $mapper,
                'strong'           => $emphasis = new ezcDocumentEzXmlToDocbookEmphasisHandler(),
                'emphasize'        => $emphasis,
                'link'             => new ezcDocumentEzXmlToDocbookLinkHandler(),
                'anchor'           => new ezcDocumentEzXmlToDocbookAnchorHandler(),
                'ol'               => $list = new ezcDocumentEzXmlToDocbookListHandler(),
                'ul'               => $list,
                'li'               => $mapper,
                'literal'          => new ezcDocumentEzXmlToDocbookLiteralHandler(),
                'line'             => new ezcDocumentEzXmlToDocbookLineHandler(),
                'table'            => new ezcDocumentEzXmlToDocbookTableHandler(),
                'tr'               => new ezcDocumentEzXmlToDocbookTableRowHandler(),
                'td'               => new ezcDocumentEzXmlToDocbookTableCellHandler(),
                'th'               => new ezcDocumentEzXmlToDocbookTableCellHandler(),
            )
        );
    }

    /**
     * Initialize destination document
     *
     * Initialize the structure which the destination document could be build
     * with. This may be an initial DOMDocument with some default elements, or
     * a string, or something else.
     *
     * @return mixed
     */
    protected function initializeDocument()
    {
        $imp = new DOMImplementation();
        $dtd = $imp->createDocumentType( 'article', '-//OASIS//DTD DocBook XML V4.5//EN', 'http://www.oasis-open.org/docbook/xml/4.5/docbookx.dtd' );
        $docbook = $imp->createDocument( 'http://docbook.org/ns/docbook', '', $dtd );
        $docbook->formatOutput = true;

        $root = $docbook->createElementNs( 'http://docbook.org/ns/docbook', 'article' );
        $docbook->appendChild( $root );

        return $root;
    }

    /**
     * Create document from structure
     *
     * Build a ezcDocumentDocument object from the structure created during the
     * visiting process.
     *
     * @param mixed $content
     * @return ezcDocumentDocbook
     */
    protected function createDocument( $content )
    {
        $document = $content->ownerDocument;

        $ezxml = new ezcDocumentDocbook();
        $ezxml->setDomDocument( $document );
        return $ezxml;
    }

    /**
     * Visit text node.
     *
     * Visit a text node in the source document and transform it to the
     * destination result
     *
     * @param DOMText $node
     * @param mixed $root
     * @return mixed
     */
    protected function visitText( DOMText $node, $root )
    {
        if ( trim( $wholeText = $node->data ) !== '' )
        {
            $text = new DOMText( $wholeText );
            $root->appendChild( $text );
        }

        return $root;
    }
}

?>
