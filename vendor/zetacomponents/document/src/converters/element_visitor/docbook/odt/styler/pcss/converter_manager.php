<?php
/**
 * File containing the ezcDocumentOdtPcssConverterManager class.
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
 * Manager for ezcDocumentOdtPcssConverter instances.
 *
 * An instance of this class is used to handle style converters. It uses the 
 * {@link ArrayAccess} interface to access style converters by the name of 
 * the CSS style attribute they handle.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentOdtPcssConverterManager extends ArrayObject
{
    /**
     * Creates a new style converter manager.
     */
    public function __construct()
    {
        parent::__construct( array(), ArrayObject::STD_PROP_LIST );
        $this->init();
    }

    /**
     * Initialize default converters.
     */
    protected function init()
    {
        $this['text-decoration']  = new ezcDocumentOdtPcssTextDecorationConverter();
        $this['font-size']        = new ezcDocumentOdtPcssFontSizeConverter();
        $this['font-name']        = new ezcDocumentOdtPcssFontNameConverter();
        $this['font-weight']      = ( $font = new ezcDocumentOdtPcssFontConverter() );
        $this['color']            = ( $color = new ezcDocumentOdtPcssColorConverter() );
        $this['background-color'] = $color;
        $this['text-align']       = ( $default = new ezcDocumentOdtDefaultPcssConverter() );
        $this['widows']           = $default;
        $this['orphans']          = $default;
        $this['text-indent']      = $default;
        $this['margin']           = new ezcDocumentOdtPcssMarginConverter();
        $this['border']           = new ezcDocumentOdtPcssBorderConverter();
        $this['break-before']     = $default;
    }

    /**
     * Sets a new style converter.
     *
     * The key must be the CSS style property this converter handles, the 
     * $value must be the style converter itself.
     * 
     * @param string $key 
     * @param ezcDocumentOdtPcssConverter $value 
     */
    public function offsetSet( $key, $value )
    {
        if ( !is_string( $key ) )
        {
            throw new ezcBaseValueException( 'key', $key, 'string' );
        }
        if ( !is_object( $value ) || !( $value instanceof ezcDocumentOdtPcssConverter ) )
        {
            throw new ezcBaseValueException(
                'value',
                $key,
                'ezcDocumentOdtPcssConverter'
            );
        }
        parent::offsetSet( $key, $value );
    }

    /**
     * Appending elements is not allowed.
     *
     * Appending a style is not allowed. Please use the array access with the 
     * style name to set a new style converter.
     * 
     * @param mixed $value 
     * @throws RuntimeException
     */
    public function append( $value )
    {
        throw new RuntimeException( 'Appending values is not allowed.' );
    }
}

?>
