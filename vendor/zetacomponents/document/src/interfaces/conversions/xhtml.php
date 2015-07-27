<?php
/**
 * File containing the ezcDocumentXhtmlConversion interface.
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
 * An interface indicating the ability to transform a document directly into
 * XHTML.
 *
 * @package Document
 * @version //autogen//
 */
interface ezcDocumentXhtmlConversion
{
    /**
     * Return document compiled to the XHTML format
     *
     * The internal document structure is compiled to the XHTML format and the
     * resulting XHTML document is returned.
     *
     * This is an optional interface for document markup languages which
     * support a direct transformation to XHTML as a shortcut.
     *
     * @return ezcDocumentXhtml
     */
    public function getAsXhtml();
}

?>
