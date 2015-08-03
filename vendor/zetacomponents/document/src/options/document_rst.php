<?php
/**
 * File containing the ezcDocumentRstOptions class.
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
 * Class containing the basic options for the ezcDocumentRst.
 *
 * @property string $docbookVisitor
 *           Classname of the docbook visitor to use.
 * @property string $xhtmlVisitor
 *           Classname of the XHTML visitor to use.
 * @property string $xhtmlVisitorOptions
 *           Options class conatining the options of the XHtml visitor.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentRstOptions extends ezcDocumentOptions
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
        $this->properties['docbookVisitor']      = 'ezcDocumentRstDocbookVisitor';
        $this->properties['xhtmlVisitor']        = 'ezcDocumentRstXhtmlVisitor';
        $this->properties['xhtmlVisitorOptions'] = new ezcDocumentHtmlConverterOptions();

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
            case 'docbookVisitor':
            case 'xhtmlVisitor':
                if ( !is_string( $value ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'classname' );
                }

                $this->properties[$name] = $value;
                break;

            case 'xhtmlVisitorOptions':
                if ( !is_object( $value ) ||
                     !( $value instanceof ezcDocumentHtmlConverterOptions ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'instanceof ezcDocumentHtmlConverterOptions' );
                }

                $this->properties[$name] = $value;
                break;

            default:
                parent::__set( $name, $value );
        }
    }
}

?>
