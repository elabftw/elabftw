<?php
/**
 * File containing the abstract ezcDocumentParser class
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
 * A base class for document parsers
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentParser implements ezcDocumentErrorReporting
{
    /**
     * XML document base options.
     *
     * @var ezcDocumentParserOptions
     */
    protected $options;

    /**
     * Additional parser properties.
     *
     * @var array
     */
    protected $properties = array(
        'errors' => array(),
    );

    /**
     * Construct new document
     *
     * @param ezcDocumentParserOptions $options
     */
    public function __construct( ezcDocumentParserOptions $options = null )
    {
        $this->options = ( $options === null ?
            new ezcDocumentParserOptions() :
            $options );
    }

    /**
     * Trigger parser error
     *
     * Emit a parser error and handle it dependiing on the current error
     * reporting settings.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param int $position
     * @return void
     */
    public function triggerError( $level, $message, $file = null, $line = null, $position = null )
    {
        if ( $level & $this->options->errorReporting )
        {
            throw new ezcDocumentParserException( $level, $message, $file, $line, $position );
        }

        // For lower error level settings, just aggregate errors
        $this->properties['errors'][] = new ezcDocumentParserException( $level, $message, $file, $line, $position );
    }

    /**
     * Return list of errors occured during visiting the document.
     *
     * May be an empty array, if on errors occured, or a list of
     * ezcDocumentVisitException objects.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->properties['errors'];
    }

    /**
     * Returns the value of the property $name.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @param string $name
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'options':
                return $this->options;
            case 'errors':
                return $this->properties['errors'];
        }

        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if the property $name does not exist
     * @throws ezcBaseValueException
     *         if $value is not accepted for the property $name
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'options':
                if ( !( $value instanceof ezcDocumentParserOptions ) )
                {
                    throw new ezcBaseValueException( 'options', $value, 'instanceof ezcDocumentParserOptions' );
                }

                $this->options = $value;
                break;

            case 'errors':
                throw new ezcBasePropertyPermissionException( $name, ezcBasePropertyPermissionException::READ );

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
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
        switch ( $name )
        {
            case 'options':
                return true;

            default:
                return false;
        }
    }
}

?>
