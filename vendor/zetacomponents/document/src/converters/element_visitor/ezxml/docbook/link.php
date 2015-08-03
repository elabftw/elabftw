<?php
/**
 * File containing the ezcDocumentEzXmlToDocbookLinkHandler class.
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
 * Visit links.
 *
 * Transform links, internal or external, into the appropriate docbook markup.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentEzXmlToDocbookLinkHandler extends ezcDocumentElementVisitorHandler
{
    /**
     * Handle a node.
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
        if ( $node->hasAttribute( 'anchor_name' ) )
        {
            // This is an internal reference
            $link = $root->ownerDocument->createElement( 'link' );
            $link->setAttribute( 'linked', $node->getAttribute( 'anchor_name' ) );
            $root->appendChild( $link );
        }
        else
        {
            switch ( true )
            {
                case $node->hasAttribute( 'url_id' ):
                    $method = 'fetchUrlById';
                    $value  = $node->getAttribute( 'url_id' );
                    break;

                case $node->hasAttribute( 'node_id' ):
                    $method = 'fetchUrlByNodeId';
                    $value  = $node->getAttribute( 'node_id' );
                    break;

                case $node->hasAttribute( 'object_id' ):
                    $method = 'fetchUrlByObjectId';
                    $value  = $node->getAttribute( 'object_id' );
                    break;

                default:
                    $converter->triggerError( E_WARNING, 'Unhandled link type.' );
                    return $root;
            }

            $link = $root->ownerDocument->createElement( 'ulink' );
            $link->setAttribute(
                'url',
                $converter->options->linkProvider->$method(
                    $value,
                    $node->hasAttribute( 'view' ) ? $node->getAttribute( 'view' ) : null,
                    $node->hasAttribute( 'show_path' ) ? $node->getAttribute( 'show_path' ) : null
                )
            );
            $root->appendChild( $link );
        }

        $converter->visitChildren( $node, $link );
        return $root;
    }
}

?>
