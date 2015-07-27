<?php
/**
 * File containing the options class for the ezcDocumentPdfFooterOptions class.
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
 * Class containing the basic options for the ezcDocumentDocbook class
 *
 * @property string $height
 *           Height of the footer, using the common measures, default: 15mm
 * @property bool $footer
 *           Set true to be rendered as a footer, and false to be
 *           rendered as header. Default: true.
 * @property bool $showDocumentTitle
 *           Display the document title in the footer, default true
 * @property bool $showDocumentAuthor
 *           Display the document author in the footer, default true
 * @property bool $showPageNumber
 *           Display the page number in the footer, default true
 * @property int $pageNumberOffset
 *           Offset for page numbers, default 0
 * @property bool $centerPageNumber
 *           Render page number in the center, by default they are
 *           rendered at the outer side of the page.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentPdfFooterOptions extends ezcDocumentOptions
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
        $this->height             = '15mm';
        $this->footer             = true;
        $this->showDocumentTitle  = true;
        $this->showDocumentAuthor = true;
        $this->showPageNumber     = true;
        $this->pageNumberOffset   = 0;
        $this->centerPageNumber   = false;

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
            case 'footer':
            case 'showDocumentTitle':
            case 'showDocumentAuthor':
            case 'showPageNumber':
            case 'centerPageNumber':
                if ( !is_bool( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'boolean' );
                }

                $this->properties[$name] = $value;
                break;

            case 'height':
                $this->properties[$name] = ezcDocumentPcssMeasure::create( $value );
                break;

            case 'pageNumberOffset':
                if ( !is_int( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'int' );
                }

                $this->properties[$name] = $value;
                break;

            default:
                parent::__set( $name, $value );
        }
    }
}

?>
