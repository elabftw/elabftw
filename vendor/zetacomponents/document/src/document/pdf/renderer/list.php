<?php
/**
 * File containing the ezcDocumentPdfListRenderer class.
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
 * Renders a list.
 *
 * Tries to render a list into the available space, and aborts if
 * not possible.
 *
 * The getListItemGenerator() determines which list items are used for list 
 * depending on the element context, like the name of the list, or optional 
 * attributes in the list providing more styling information.
 *
 * List items styles cannot be overwritten using CSS with this renderer.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfListRenderer extends ezcDocumentPdfBlockRenderer
{
    /**
     * Process to render block contents.
     * 
     * @param ezcDocumentPdfPage $page 
     * @param ezcDocumentPdfHyphenator $hyphenator 
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $block 
     * @param ezcDocumentPdfMainRenderer $mainRenderer 
     * @return void
     */
    protected function process( ezcDocumentPdfPage $page, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $block, ezcDocumentPdfMainRenderer $mainRenderer )
    {
        $childNodes = $block->childNodes;
        $nodeCount  = $childNodes->length;
        $listItem   = 1;

        $itemGenerator = $this->getListItemGenerator( $block );

        for ( $i = 0; $i < $nodeCount; ++$i )
        {
            $child = $childNodes->item( $i );
            if ( $child->nodeType !== XML_ELEMENT_NODE )
            {
                continue;
            }

            // Default to docbook namespace, if no namespace is defined
            $namespace = $child->namespaceURI === null ? 'http://docbook.org/ns/docbook' : $child->namespaceURI;
            if ( ( $namespace !== 'http://docbook.org/ns/docbook' ) ||
                 ( $child->tagName !== 'listitem' ) )
            {
                continue;
            }

            $renderer = new ezcDocumentPdfListItemRenderer( $this->driver, $this->styles, $itemGenerator, $listItem++ );
            $renderer->renderNode( $page, $hyphenator, $tokenizer, $child, $mainRenderer );
        }
    }

    /**
     * Get list item generator
     *
     * Get list item generator for the list generator.
     * 
     * @param ezcDocumentLocateableDomElement $block 
     * @return ezcDocumentListItemGenerator
     */
    protected function getListItemGenerator( ezcDocumentLocateableDomElement $block )
    {
        switch ( $block->tagName )
        {
            case 'itemizedlist':
                if ( $block->hasAttribute( 'mark' ) )
                {
                    return new ezcDocumentBulletListItemGenerator( $block->getAttribute( 'mark' ) );
                }
                return new ezcDocumentBulletListItemGenerator();

            case 'orderedlist':
                if ( !$block->hasAttribute( 'numeration' ) )
                {
                    return new ezcDocumentNumberedListItemGenerator();
                }

                switch ( $block->getAttribute( 'numeration' ) )
                {
                    case 'arabic':
                        return new ezcDocumentNumberedListItemGenerator();
                    case 'loweralpha':
                        return new ezcDocumentAlphaListItemGenerator( ezcDocumentAlnumListItemGenerator::LOWER );
                    case 'lowerroman':
                        return new ezcDocumentRomanListItemGenerator( ezcDocumentAlnumListItemGenerator::LOWER );
                    case 'upperalpha':
                        return new ezcDocumentAlphaListItemGenerator( ezcDocumentAlnumListItemGenerator::UPPER );
                    case 'upperroman':
                        return new ezcDocumentRomanListItemGenerator( ezcDocumentAlnumListItemGenerator::UPPER );
                    default:
                        return new ezcDocumentNumberedListItemGenerator();
                }

            default:
                return new ezcDocumentNoListItemGenerator();
        }
    }
}

?>
