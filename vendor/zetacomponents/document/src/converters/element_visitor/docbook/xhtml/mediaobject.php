<?php
/**
 * File containing the ezcDocumentDocbookToHtmlMediaObjectHandler class.
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
 * Visit media objects
 *
 * Media objects are all kind of other media types, embedded in the
 * document, like images.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToHtmlMediaObjectHandler extends ezcDocumentDocbookToHtmlBaseHandler
{
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
        // Get image resource
        $resource = $node->getElementsBytagName( 'imagedata' )->item( 0 );

        $image = $root->ownerDocument->createElement( 'img' );

        // Transform attributes
        $attributes = array(
            'width'   => 'width',
            'depth'   => 'height',
            'fileref' => 'src',
        );
        foreach ( $attributes as $src => $dst )
        {
            if ( $resource->hasAttribute( $src ) )
            {
                $image->setAttribute( $dst, htmlspecialchars( $resource->getAttribute( $src ) ) );
            }
        }

        // Check if the image has a description
        if ( ( $textobject = $node->getElementsBytagName( 'textobject' ) ) &&
               ( $textobject->length > 0 ) )
        {
            $image->setAttribute( 'alt', htmlspecialchars( trim( $textobject->item( 0 )->textContent ) ) );
        }
        else
        {
            // Always set some alt value, as this is required by XHtml
            $image->setAttribute( 'alt', htmlspecialchars( $resource->getAttribute( 'src' ) ) );
        }

        // Check if the image has additional description assigned. In such a
        // case we wrap the image and the text inside another block.
        if ( ( $textobject = $node->getElementsBytagName( 'caption' ) ) &&
               ( $textobject->length > 0 ) )
        {
            $textobject = $textobject->item( 0 );
            $wrapper = $root->ownerDocument->createElement( 'div' );
            $wrapper->setAttribute( 'class', 'image' );
            $wrapper->appendChild( $image );

            // Decorate the childs of the caption node recursively, as it might
            // contain additional markup.
            $textobject = $converter->visitChildren( $textobject, $wrapper );
            $image = $wrapper;
        }

        $root->appendChild( $image );
        return $root;
    }
}

?>
