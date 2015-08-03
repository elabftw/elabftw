<?php
/**
 * File containing the ezcDocumentErrorReporting interface.
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
 * Interface for error reporting.
 *
 * @package Document
 * @version //autogen//
 */
interface ezcDocumentErrorReporting
{
    /**
     * Trigger parser error.
     *
     * Emit a parser error and handle it dependiing on the current error
     * reporting settings.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param int $position
     */
    public function triggerError( $level, $message, $file = null, $line = null, $position = null );

    /**
     * Return list of errors occured during visiting the document.
     *
     * May be an empty array, if on errors occured, or a list of
     * ezcDocumentVisitException objects.
     *
     * @return array
     */
    public function getErrors();
}

?>
