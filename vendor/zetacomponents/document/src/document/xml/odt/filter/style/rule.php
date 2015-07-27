<?php
/**
 * File containing the ezcDocumentOdtStyleFilterRule interface.
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
 * Interface for style filter rules.
 *
 * A style filter rule must implement this interface.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
interface ezcDocumentOdtStyleFilterRule
{
    /**
     * Returns if the given $odtElement is handled by the rule.
     * 
     * @param DOMElement $odtElement 
     * @return bool
     */
    public function handles( DOMElement $odtElement );

    /**
     * Filter the given $odtElement based on the style information available 
     * through $styleInferencer.
     *
     * This method will only be called when handles returned true for the given 
     * $odtElement. The method may manipulate the $odtElement, especially its 
     * attributes, based on the style information.
     * 
     * @param DOMElement $odtElement 
     * @param ezcDocumentOdtStyleInferencer $styleInferencer
     */
    public function filter( DOMElement $odtElement, ezcDocumentOdtStyleInferencer $styleInferencer );
}

?>
