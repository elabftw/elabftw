<?php
/**
 * File containing the ezcDocumentErroneousXmlException class.
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
 * General exception container for the Document component.
 *
 * @package Document
 * @version //autogentag//
 */
class ezcDocumentErroneousXmlException extends ezcDocumentException
{
    /**
     * Errors occured during parsing process.
     *
     * @var array
     */
    protected $errors;

    /**
     * Construct exception from array with XML errors.
     *
     * @param array $errors
     */
    public function __construct( array $errors )
    {
        $this->errors = $errors;
        parent::__construct( "Errors occured while parsing the XML." );
    }

    /**
     * Return array with XML errors.
     *
     * @return array
     */
    public function getXmlErrors()
    {
        return $this->errors;
    }
}

?>
