<?php
/**
 * File containing the ezcDocumentXsltConverterOptions class.
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
 * @property string $xslt
 *           Path to XSLT, which should be used for the conversion.
 * @property array $parameters
 *           List of aparameters for the XSLT transformation. Parameters are
 *           given as array, with the structure array( 'namespace' => array(
 *           'option' => 'value' ) ), where namespace may also be an empty
 *           string.
 * @property boolean $failOnError
 *           Boolean indicator if the conversion should be aborted, when errors
 *           occurs with an exception, or if the errors just should be ignored.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentXsltConverterOptions extends ezcDocumentConverterOptions
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
        if ( !isset( $this->properties['xslt'] ) )
        {
            $this->properties['xslt'] = null;
        }

        if ( !isset( $this->properties['parameters'] ) )
        {
            $this->parameters = array();
        }

        $this->properties['failOnError'] = false;

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
            case 'xslt':
                $this->properties[$name] = (string) $value;
                break;

            case 'parameters':
                if ( !is_array( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'array' );
                }

                $this->properties[$name] = $value;
                break;

            case 'failOnError':
                if ( !is_bool( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'boolean' );
                }

                $this->properties[$name] = (bool) $value;
                break;

            default:
                parent::__set( $name, $value );
        }
    }
}

?>
