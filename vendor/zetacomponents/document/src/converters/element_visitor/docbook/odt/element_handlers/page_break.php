<?php
/**
 * File containing the ezcDocumentDocbookToOdtPageBreakHandler class.
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
 * Visit page-breaks.
 *
 * Visit docbook <beginpage/> and transform them into ODT <text:soft-page-break/>.
 *
 * Note that OpenOffice.org does not pay attention to these page-break 
 * information, but expects page-breaks to be encoded in styles. Therefore, 
 * additional page-break handling happens in {@link 
 * ezcDocumentOdtPcssParagraphStylePreprocessor}.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentDocbookToOdtPageBreakHandler extends ezcDocumentDocbookToOdtBaseHandler
{
    /**
     * Handle a node
     *
     * Handle / transform a given node, and return the result of the
     * conversion.
     *
     * @param ezcDocumentElementVisitorConverter $converter
     * @param DOMElement $node
     * @param mixed $root
     * @return mixed
     */
    public function handle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $pageBreak = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'text:soft-page-break'
            )
        );

        return $root;
    }
}

?>
