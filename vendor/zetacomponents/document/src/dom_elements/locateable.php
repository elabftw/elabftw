<?php
/**
 * File containing the ezcDocumentLocateableDomElement class
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
 * Custom DOMElement extension
 *
 * Extends the DOMElement class, to generate, store and cache the location ID
 * of the curretn element.
 *
 * The location ID is based on the parent elements ID, concatenated with the
 * current element name, together with relevant attributes, possible element
 * classes and a possible element ID.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentLocateableDomElement extends DOMElement implements ezcDocumentLocateable
{
    /**
     * Calculated location Id
     *
     * @var string
     */
    protected $locationId = null;

    /**
     * Attributes relevant enough to be included in the location identifier.
     * Contents of the class attribute are annotated differently, so it will
     * not be included here.
     *
     * @var array
     */
    protected $relevantAttributes = array(
        'Role',
    );

    /**
     * Get elements location ID
     *
     * Return the elements location ID, based on the factors described in the
     * class header.
     *
     * @return string
     */
    public function getLocationId()
    {
        if ( $this->locationId !== null )
        {
            return $this->locationId;
        }

        // If we did not reach the root node yet, request the parent location
        // id to prepend to generated ID:
        $locationId = '';
        if ( !$this->parentNode instanceof DOMDocument )
        {
            $locationId = $this->parentNode->getLocationId();
        }

        // Append current node information
        $locationId .= '/' . $this->tagName;

        // Check for relevant attributes, so that they are also included
        foreach ( $this->relevantAttributes as $attribute )
        {
            if ( $this->hasAttribute( $attribute ) )
            {
                $locationId .= '[' . $attribute . '=' . preg_replace( '([^a-z0-9_-]+)', '_', strtolower( $this->getAttribute( $attribute ) ) ) . ']';
            }
        }

        // Append class, if set
        if ( $this->hasAttribute( 'class' ) )
        {
            $locationId .= '.' . preg_replace( '([^a-z0-9_-]+)', '_', strtolower( $this->getAttribute( 'class' ) ) );
        }

        // Append ID, if set
        if ( $this->hasAttribute( 'ID' ) )
        {
            $locationId .= '#' . preg_replace( '([^a-z0-9_-]+)', '_', strtolower( $this->getAttribute( 'ID' ) ) );
        }

        return $locationId;
    }
}
?>
