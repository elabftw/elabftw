<?php
/**
 * File containing the ezcDocumentOdtFormattingPropertyCollection class.
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
 * Class to carry and manage {@link ezcDocumentOdtFormattingProperties}.
 *
 * An instance of this class is used in an {@link ezcDocumentOdtStyle} to carry 
 * various formatting properties of class {@link 
 * ezcDocumentOdtFormattingProperties}.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtFormattingPropertyCollection
{
    /**
     * Formatting properties. 
     * 
     * @var array(const=>ezcDocumentOdtFormattingProperties)
     */
    private $properties = array();

    /**
     * Sets the given $properties.
     *
     * If properties of the same type are already set, an exception is thrown.  
     * If you don't care if properties are overwriten, use {@link 
     * replaceProperties()}. You can check if properties of a certain type are 
     * already set using {@link hasProperties()} and retrieve them using {@link 
     * getProperties()}.
     * 
     * @param ezcDocumentOdtFormattingProperties $properties 
     *
     * @throws ezcDocumentOdtFormattingPropertiesAlreadyExistException
     */
    public function setProperties( ezcDocumentOdtFormattingProperties $properties )
    {
        if ( isset( $this->properties[$properties->type] ) )
        {
            throw new ezcDocumentOdtFormattingPropertiesExistException(
                $properties
            );
        }
        $this->replaceProperties( $properties );
    }

    /**
     * Sets the given $properties, even if properties of the same type are 
     * already set.
     *
     * Similar to {@link setProperties()} but silently overwrites properties 
     * of the same type, if they exist.
     * 
     * @param ezcDocumentOdtFormattingProperties $properties 
     */
    public function replaceProperties( ezcDocumentOdtFormattingProperties $properties )
    {
        $this->properties[$properties->type] = $properties;
    }

    /**
     * Returns if properties of $type are set.
     *
     * Returns true, if properties of $type are set in this collection, 
     * otherwise false. $type must be one of the {@link 
     * ezcDocumentOdtFormattingProperties} PROPERTIES_* constants.
     * 
     * @param const $type 
     * @return bool
     */
    public function hasProperties( $type )
    {
        return isset( $this->properties[$type] );
    }

    /**
     * Returns properties of the given $type.
     *
     * If properties of the given $type are set, the corresponding object is 
     * returned. Otherwise null is returned. You can check if properties of a 
     * given $type are set using {@link hasProperties()}. $type must be one 
     * of the {@link ezcDocumentOdtFormattingProperties} FAMILY_ 
     * constants.
     * 
     * @param const $type 
     * @return ezcDocumentOdtFormattingProperties|null
     */
    public function getProperties( $type )
    {
        if ( $this->hasProperties( $type ) )
        {
            return $this->properties[$type];
        }
        return null;
    }
}

?>
