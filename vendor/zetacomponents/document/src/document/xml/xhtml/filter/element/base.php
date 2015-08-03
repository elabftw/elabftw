<?php
/**
 * File containing the abstract ezcDocumentXhtmlElementBaseFilter base class.
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
 * Filter for XHTML elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
abstract class ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     */
    abstract public function filterElement( DOMElement $element );

    /**
     * Check if filter handles the current element
     *
     * Returns a boolean value, indicating weather this filter can handle
     * the current element.
     *
     * @param DOMElement $element
     * @return void
     */
    abstract public function handles( DOMElement $element );

    /**
     * Is block level element
     *
     * Returns true, if the element is a block level element in XHtml, and
     * false otherwise.
     *
     * @param DOMElement $element
     * @return boolean
     */
    protected function isBlockLevelElement( DOMElement $element )
    {
        return in_array(
            $element->tagName,
            array(
                'address',
                'blockquote',
                'body',
                'center',
                'del',
                'dir',
                'div',
                'dl',
                'fieldset',
                'form',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'hr',
                'ins',
                'li',
                'menu',
                'noframes',
                'noscript',
                'ol',
                'p',
                'pre',
                'table',
                'th',
                'td',
                'ul',
            )
        );
    }

    /**
     * Check if node is an inline element
     *
     * Check if the passed node is an inline element, eg. may occur inside a
     * text block, like a paragraph.
     *
     * @param DOMNode $node
     * @return bool
     */
    protected function isInlineElement( DOMNode $node )
    {
        return (
            ( $node->nodeType === XML_TEXT_NODE ) ||
            ( ( $node->nodeType === XML_ELEMENT_NODE ) &&
              in_array( $node->tagName, array(
                'a',
                'abbr',
                'acronym',
                'applet',
                'b',
                'basefont',
                'bdo',
                'big',
                'button',
                'cite',
                'code',
                'del',
                'dfn',
                'em',
                'font',
                'i',
                'img',
                'ins',
                'input',
                'iframe',
                'kbd',
                'label',
                'map',
                'object',
                'q',
                'samp',
                'script',
                'select',
                'small',
                'span',
                'strong',
                'sub',
                'sup',
                'textarea',
                'tt',
                'var',
              ), true )
            )
        );
    }

    /**
     * Is current element placed inline
     *
     * Checks if the current element is placed inline, which means, it is
     * either a descendant of some other inline element, or part of a
     * paragraph.
     *
     * @param DOMElement $element
     * @return void
     */
    protected function isInline( DOMElement $element )
    {
        $toCheck = $element;
        do {
            if ( in_array( $toCheck->getProperty( 'type' ), array(
                    'para',
                    'subtitle',
                    'title',
                    'term',
                    'abbrev',
                    'acronym',
                    'anchor',
                    'attribution',
                    'author',
                    'citation',
                    'citetitle',
                    'email',
                    'emphasis',
                    'footnoteref',
                    'link',
                    'literal',
                    'literallayout',
                    'quote',
                    'subscript',
                    'superscript',
                    'ulink',
                ) ) )
            {
                return true;
            }
        } while ( ( $toCheck = $toCheck->parentNode ) &&
                  ( $toCheck instanceof DOMElement ) );

        return false;
    }

    /**
     * Check for element class
     *
     * Check if element has the given class in its class attribute. Returns
     * true, if it is contained, or false, if not.
     *
     * @param DOMElement $element
     * @param string $class
     * @return bool
     */
    protected function hasClass( DOMElement $element, $class )
    {
        return ( $element->hasAttribute( 'class' ) &&
                 preg_match(
                    '((?:^|\s)' . preg_quote( $class ) . '(?:\s|$))',
                    $element->getAttribute( 'class' )
                 )
        );
    }

    /**
     * Shows a string representation of the current node.
     *
     * Is only there for debugging purposes
     * 
     * @param DOMElement $element 
     * @param bool $newLine
     * @access private
     */
    protected function showCurrentNode( DOMElement $element, $newLine = true )
    {
        if ( $element->parentNode &&
             ( $element->parentNode instanceof DOMElement ) )
        {
            $this->showCurrentNode( $element->parentNode, false );
        }

        echo '> ', $element->tagName;

        if ( $element->getProperty( 'type' ) !== false )
        {
            echo ' (', $element->getProperty( 'type' ), ')';
        }

        echo $newLine ? "\n" : ' ';
    }
}

?>
