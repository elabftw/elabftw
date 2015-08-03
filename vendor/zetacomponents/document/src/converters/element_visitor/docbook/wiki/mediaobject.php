<?php
/**
 * File containing the ezcDocumentDocbookToWikiMediaObjectHandler class.
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
class ezcDocumentDocbookToWikiMediaObjectHandler extends ezcDocumentDocbookToWikiBaseHandler
{
    /**
     * Extract image parameters
     *
     * Extract the image parameters from a media object or inline media object
     * node in the Docbook document. Returns an array with named keys
     * containing the directive parameters.
     *
     * @param ezcDocumentElementVisitorConverter $converter
     * @param DOMElement $node
     * @return array
     */
    protected function getImageParameters( ezcDocumentElementVisitorConverter $converter, DOMElement $node )
    {
        $resource = $node->getElementsBytagName( 'imagedata' )->item( 0 );
        $options = array(
            'resource' => $resource->getAttribute( 'fileref' ),
        );

        // Get image resource

        // Transform attributes
        $attributes = array(
            'width'    => 'width',
            'depth'    => 'height',
        );
        foreach ( $attributes as $src => $dst )
        {
            if ( $resource->hasAttribute( $src ) )
            {
                $options[$dst] = $resource->getAttribute( $src );
            }
        }

        // Check if the image has a description
        if ( ( $textobject = $node->getElementsBytagName( 'textobject' ) ) &&
               ( $textobject->length > 0 ) )
        {
            $options['alt'] = trim( $textobject->item( 0 )->textContent );
        }

        // Check if the image has additional description assigned. In such a
        // case we wrap the image and the text inside another block.
        if ( ( $textobject = $node->getElementsBytagName( 'caption' ) ) &&
               ( $textobject->length > 0 ) )
        {
            $textobject = $textobject->item( 0 );

            // Decorate the childs of the caption node recursively, as it might
            // contain additional markup.
            $options['text'] = preg_replace( '(\s+)', ' ', $converter->visitChildren( $textobject, '' ) );
        }

        return $options;
    }

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
        $image = $this->getImageParameters( $converter, $node );

        if ( isset( $image['alt'] ) || isset( $image['text'] ) )
        {
            $root .= sprintf( "\n{{%s|%s}}\n\n",
                $image['resource'],
                isset( $image['text'] ) ? $image['text'] : $image['alt']
            );
        }
        else
        {
            $root .= sprintf( "\n{{%s}}\n\n", $image['resource'] );
        }

        return $root;
    }
}

?>
