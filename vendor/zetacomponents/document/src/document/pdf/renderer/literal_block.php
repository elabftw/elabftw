<?php
/**
 * File containing the ezcDocumentPdfLiteralBlockRenderer class.
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
 * Renders a literal block / code section.
 *
 * Renders a code section / literal block, which especially means, that
 * whitespaces are not omitted or reduced, but preserved in the output.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfLiteralBlockRenderer extends ezcDocumentPdfWrappingTextBoxRenderer
{
    /**
     * Renders a literal block.
     *
     * @param ezcDocumentPdfPage $page 
     * @param ezcDocumentPdfHyphenator $hyphenator 
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $text 
     * @param ezcDocumentPdfMainRenderer $mainRenderer 
     * @return bool
     */
    public function renderNode( ezcDocumentPdfPage $page, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $text, ezcDocumentPdfMainRenderer $mainRenderer )
    {
        // Use a special tokenizer and hyphenator for literal blocks
        return parent::renderNode(
            $page,
            new ezcDocumentPdfDefaultHyphenator(),
            new ezcDocumentPdfLiteralTokenizer(),
            $text,
            $mainRenderer
        );
    }
}
?>
