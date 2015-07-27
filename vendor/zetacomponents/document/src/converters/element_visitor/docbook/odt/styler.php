<?php
/**
 * File containing the ezcDocumentOdtStyler interface.
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
 * @access private
 * @package Document
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Interface for ODT stylers.
 *
 * This interface must be implemented by stylers provided in the {@link 
 * ezcDocumentDocbookToOdtConverterOptions}.
 *
 * @access private
 * @package Document
 * @version //autogen//
 */
interface ezcDocumentOdtStyler
{
    /**
     * Initialize the styler with the given $odtDocument.
     *
     * This method *must* be called *before* {@link applyStyles()} is called 
     * at all. Otherwise an exception will be thrown. This method is called by 
     * the {@link ezcDocumentDocbookToOdtConverter} whenever a new ODT document 
     * is to be converted.
     * 
     * @param DOMDocument $odtDocument
     */
    public function init( DOMDocument $odtDocument );

    /**
     * Applies the style information associated with $docBookElement to 
     * $odtElement.
     *
     * This method must apply the style information associated with the given 
     * $docBookElement to the $odtElement given.
     * 
     * @param ezcDocumentLocateable $docBookElement 
     * @param DOMElement $odtElement 
     * @throws ezcDocumentOdtStylerNotInitializedException
     *         if the styler has not been initialized using the {@link init()} 
     *         method, yet. Initialization is performed in the {@link 
     *         ezcDocumentDocbookToOdtConverter}.
     */
    public function applyStyles( ezcDocumentLocateable $docBookElement, DOMElement $odtElement );
}

?>
