<?php
/**
 * File containing the ezcDocumentValidationError class
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
 * Unifies different errors into a single structure for all kinds of validation
 * errors. The validation error message can be fetched using the __toString()
 * method, while the original error is still be available, fi required.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentValidationError
{
    /**
     * Original error object
     *
     * @var mixed
     */
    protected $error;

    /**
     * Transformed error message.
     *
     * @var string
     */
    protected $message;

    /**
     * textual mapping for libxml error types.
     *
     * @var array
     */
    protected static $errorTypes = array(
        LIBXML_ERR_WARNING => 'Warning',
        LIBXML_ERR_ERROR   => 'Error',
        LIBXML_ERR_FATAL   => 'Fatal error',
    );

    /**
     * Create validation error object
     *
     * @param string $message
     * @param mixed $error
     * @return void
     */
    protected function __construct( $message, $error = null )
    {
        $this->message = $message;
        $this->error   = $error;
    }

    /**
     * Get original error object
     *
     * @return mixed
     */
    public function getOriginalError()
    {
        return $this->error;
    }

    /**
     * Convert libXML error to string
     *
     * @return void
     */
    public function __toString()
    {
        return $this->message;
    }

    /**
     * Create from LibXmlError
     *
     * Create a validation error object from a LibXmlError error object.
     *
     * @param LibXMLError $error
     * @return ezcDocumentValidationError
     */
    public static function createFromLibXmlError( LibXMLError $error )
    {
        return new ezcDocumentValidationError(
            sprintf( "%s in %d:%d: %s.",
                self::$errorTypes[$error->level],
                $error->line,
                $error->column,
                trim( $error->message )
            ),
            $error
        );
    }

    /**
     * Create validation error from Exception
     *
     * @param Exception $e
     * @return ezcDocumentValidationError
     */
    public static function createFromException( Exception $e )
    {
        return new ezcDocumentValidationError(
            $e->getMessage(),
            $e
        );
    }
}

?>
