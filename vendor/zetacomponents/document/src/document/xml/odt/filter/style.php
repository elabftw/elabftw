<?php
/**
 * File containing the ezcDocumentOdtStyleFilter class.
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
 * Filter mechanism based on ODT style information.
 *
 * This filter consists of filte rules, which inference semantics for ODT
 * elements based on their attached style information.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtStyleFilter extends ezcDocumentOdtBaseFilter
{
    /**
     * Style filter rules. 
     * 
     * @var array(ezcDocumentOdtStyleFilterRule)
     */
    protected $rules = array();

    /**
     * Style inferencer. 
     * 
     * @var ezcDocumentOdtStyleInferencer
     */
    protected $styleInferencer;

    /**
     * Creates a new style filter.
     *
     * @todo Make configurable.
     */
    public function __construct()
    {
        $this->rules = array(
            new ezcDocumentOdtEmphasisStyleFilterRule(),
            new ezcDocumentOdtListLevelStyleFilterRule(),
        );
    }

    /**
     * Filter ODT document.
     *
     * Filter for the document, which may modify / restructure a document and
     * assign semantic information bits to the elements in the tree.
     *
     * @param DOMDocument $dom
     * @return DOMDocument
     */
    public function filter( DOMDocument $dom )
    {
        $this->styleInferencer = new ezcDocumentOdtStyleInferencer( $dom );
        $xpath = new DOMXPath( $dom );
        $xpath->registerNamespace( 'office', ezcDocumentOdt::NS_ODT_OFFICE );
        $root = $xpath->query( '//office:body' )->item( 0 );
        $this->filterNode( $root );
    }

    /**
     * Filter node
     *
     * Depending on the element name, it parents and maybe element attributes
     * semantic information is assigned to nodes.
     *
     * @param DOMElement $element
     * @return void
     */
    protected function filterNode( DOMElement $element )
    {
        $style = null;
        foreach ( $this->rules as $rule )
        {
            if ( $rule->handles( $element ) )
            {
                $rule->filter( $element, $this->styleInferencer );
            }
        }

        foreach ( $element->childNodes as $child )
        {
            if ( $child->nodeType === XML_ELEMENT_NODE )
            {
                $this->filterNode( $child );
            }
        }
    }
}

?>
