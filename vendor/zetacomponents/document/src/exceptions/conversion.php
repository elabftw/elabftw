<?php
/**
 * File containing the ezcDocumentConversionException class.
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
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Exception thrown, when the RST parser could not parse asome token sequence.
 *
 * @package Document
 * @version //autogentag//
 */
class ezcDocumentConversionException extends ezcDocumentException
{
    /**
     * Mapping of error levels to strings
     *
     * @var array
     */
    protected $levelMapping = array(
        E_NOTICE  => 'Notice',
        E_WARNING => 'Warning',
        E_ERROR   => 'Error',
        E_PARSE   => 'Fatal error',
    );

    /**
     * Error string
     *
     * String describing the general type of this error
     *
     * @var string
     */
    protected $errorString = 'Conversion error';

    /**
     * Original exception message
     *
     * @var string
     */
    public $parseError;

    /**
     * Construct exception from errnous string and current position
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param int $position
     * @param Exception $exception
     * @return void
     */
    public function __construct( $level, $message, $file = null, $line = null, $position = null, Exception $exception = null )
    {
        $this->parseError = $message;

        $message = "{$this->errorString}: {$this->levelMapping[$level]}: '$message'";

        if ( $file !== null )
        {
            $message .= " in file '$file'";
        }

        if ( $line !== null )
        {
            $message .= " in line $line at position $position";
        }

        parent::__construct( $message . '.', 0, $exception );
    }
}

?>
