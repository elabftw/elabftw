<?php
/**
 * File containing the ezcDocumentOdtListLevelStyleBullet class.
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
 * Bullet list-level style.
 *
 * @property-read int $level
 *                The list level, starting with 1.
 * @property ezcDocumentOdtStyle|null $textStyle
 *           Text style for list bullet / number formatting.
 * @property string $bulletChar
 *           Character to use for bullets (only 1 char allowed).
 * @property string $numPrefix
 *           Prefix for list bullets.
 * @property string $numSuffix
 *           Suffix for list bullets.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtListLevelStyleBullet extends ezcDocumentOdtListLevelStyle
{
    /**
     * Properties
     * 
     * @var array(string=>mixed)
     */
    private $properties = array(
        'bulletChar' => '',
        'numPrefix'  => '',
        'numSuffix'  => '',
    );

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
            case 'bulletChar':
                if ( !is_string( $value ) || iconv_strlen( $value, 'UTF-8' ) !== 1 )
                {
                    throw new ezcBaseValueException( $name, $value, 'string, length = 1' );
                }
                break;
            case 'numPrefix':
            case 'numSuffix':
                if ( !is_string( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'string' );
                }
                break;
            default:
                return parent::__set( $name, $value );
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
        if ( array_key_exists( $name, $this->properties ) )
        {
            return $this->properties[$name];
        }
        return parent::__get( $name );
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
        return array_key_exists( $name, $this->properties ) || parent::__isset( $name );
    }
}

?>
