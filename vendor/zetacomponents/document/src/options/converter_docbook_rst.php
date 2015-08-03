<?php
/**
 * File containing the ezcDocumentDocbookToRstConverterOptions class.
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
 * Class containing the basic options for the ezcDocumentEzp3Xml class
 *
 * @property array $headerTypes
 *           Array of special characters to use fore headings in RST output. If
 *           two chracters are given, the heading will be rendered with an over
 *           and underline.
 * @property int $wordWrap
 *           Maximum number of characters per line. The contents will be
 *           wrapped at the given position. Defaults to 78.
 * @property int $itemListCharacter
 *           Character used for item lists. Defaults to -, valid are also:
 *           *, +, •, ‣, ⁃
 *           wrapped at the given position. Defaults to 78.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToRstConverterOptions extends ezcDocumentConverterOptions
{
    /**
     * Constructs an object with the specified values.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if $options contains a property not defined
     * @throws ezcBaseValueException
     *         if $options contains a property with a value not allowed
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        $this->headerTypes = array(
            '==',
            '--',
            '=',
            '-',
            '^',
            '~',
            '`',
            '*',
            ':',
            '+',
            '/',
            '.',
        );
        $this->wordWrap          = 78;
        $this->itemListCharacter = '-';

        parent::__construct( $options );
    }

    /**
     * Sets the option $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name is not defined
     * @throws ezcBaseValueException
     *         if $value is not correct for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'headerTypes':
                if ( !is_array( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'array' );
                }

                $this->properties[$name] = $value;
                break;

            case 'wordWrap':
                if ( !is_numeric( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'int' );
                }

                $this->properties[$name] = (int) $value;
                break;

            case 'itemListCharacter':
                if ( !in_array( $value, $listCharacters = array(
                        '*', '-', '+',
                        "\xe2\x80\xa2", "\xe2\x80\xa3", "\xe2\x81\x83"
                    ), true ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'Item list characters: ' . implode( ', ', $listCharacters ) );
                }

                $this->properties[$name] = $value;
                break;

            default:
                parent::__set( $name, $value );
        }
    }
}

?>
