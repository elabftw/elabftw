<?php
/**
 * File containing ezcDocumentConverterOptions class.
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
 * Class containing the basic options for the ezcDocumentConverter
 *
 * @property int $errorReporting
 *           Error reporting level. All errors with a severity greater or equel
 *           then the defined level are converted to exceptions. All other
 *           errors are just stored in errors property of the parser class.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentConverterOptions extends ezcBaseOptions
{
    /**
     * Container to hold the properties
     *
     * @var array(string=>mixed)
     */
    protected $properties = array(
        'errorReporting' => 15, // E_PARSE | E_ERROR | E_WARNING | E_NOTICE
    );

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
            case 'errorReporting':
                if ( !is_int( $value ) ||
                     ( ( $value & E_PARSE ) === 0 ) )
                {
                    throw new ezcBaseValueException( $name, $value, 'int & E_PARSE' );
                }

                $this->properties[$name] = $value;
                break;

            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }
}

?>
