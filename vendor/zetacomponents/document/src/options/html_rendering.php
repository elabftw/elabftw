<?php
/**
 * File containing the ezcDocumentHtmlConverterOptions class.
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
 * @property bool $dublinCoreMetadata
 *           Use the dublincore meta element names for metadata in HTML.
 * @property bool $formatOutput
 *           Indent the XHtml output
 * @property array $styleSheets
 *           Array of stylesheet URLs to embed in the HTML header, if there is
 *           one.
 * @property string $styleSheet
 *           Stylesheet to embed in the HTML header, if the property
 *           $stylesheets has not been set. This property contains the default
 *           stylesheet for HTML output.
 * @property string $headerLevel
 *           Header level to start with. Is only used by inline HTML renderer.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentHtmlConverterOptions extends ezcDocumentConverterOptions
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
        $this->dublinCoreMetadata = false;
        $this->formatOutput       = false;
        $this->styleSheets        = null;
        $this->styleSheet         = file_get_contents( dirname( __FILE__ ) . '/data/html_style.css' );
        $this->headerLevel        = 1;

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
            case 'formatOutput':
            case 'dublinCoreMetadata':
                if ( !is_bool( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'boolean' );
                }

                $this->properties[$name] = $value;
                break;

            case 'styleSheets':
                if ( !is_array( $value ) &&
                     !is_null( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'null OR array( URL )' );
                }

                $this->properties[$name] = $value;
                break;

            case 'styleSheet':
                $this->properties[$name] = (string) $value;
                break;

            case 'headerLevel':
                if ( !is_int( $value ) ||
                     ( $value > 6 ) ||
                     ( $value < 0 ) )
                {
                    throw new ezcBaseValueException( $name, $value, '0 < int < 7' );
                }
                $this->properties[$name] = $value;
                break;

            default:
                parent::__set( $name, $value );
        }
    }
}

?>
