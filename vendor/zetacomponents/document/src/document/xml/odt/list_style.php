<?php
/**
 * File containing the ezcDocumentOdtListStyle class.
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
 * Class for ODT list styles.
 *
 * @property-read string $name The style name.
 * @property ArrayObject(ezcDocumentOdtListLevelStyle) $listLevels
 *           List-level styles.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtListStyle
{
    /**
     * Properties
     * 
     * @var array(string=>mixed)
     */
    protected $properties = array(
        'name'       => null,
        'listLevels' => null,
    );

    /**
     * Creates a new list style.
     *
     * Creates a new list style with the given $name.
     * 
     * @param string $name 
     */
    public function __construct( $name )
    {
        $this->properties['name']   = $name;
        $this->listLevels = new ArrayObject();
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'name':
                throw new ezcBasePropertyPermissionException( $name, ezcBasePropertyPermissionException::READ );
            case 'listLevels':
                if ( !is_object( $value ) || !( $value instanceof ArrayObject ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'ArrayObject' );
                }
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
        $this->properties[$name] = $value;
    }

    /**
     * Returns the value of the property $name.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @ignore
     */
    public function __get( $name )
    {
        if ( $this->__isset( $name ) )
        {
            return $this->properties[$name];
        }
        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Returns true if the property $name is set, otherwise false.
     *
     * @param string $name     
     * @return bool
     * @ignore
     */
    public function __isset( $name )
    {
        return array_key_exists( $name, $this->properties );
    }
}

?>
