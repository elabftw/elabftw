<?php
/**
 * File containing the ezcDocumentPdfTextBlockRenderer class
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
 * Text block renderer
 *
 * Renders a text into a text block.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfTextBlockRenderer extends ezcDocumentPdfTextBoxRenderer
{
    /**
     * Estimate height
     *
     * Estimate required height to render the given text node.
     *
     * @param float $width
     * @param ezcDocumentPdfHyphenator $hyphenator
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $text
     * @return float
     */
    public function estimateHeight( $width, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $text )
    {
        // Inference page styles
        $styles = $this->styles->inferenceFormattingRules( $text );

        // @todo: Apply: Margin, border, padding

        // Iterate over tokens and try to fit them in the current line, use
        // hyphenator to split words.
        $tokens = $this->tokenize( $text, $tokenizer );
        $lines  = $this->fitTokensInLines( $tokens, $hyphenator, $width );

        // Aggregate total height
        $height = 0;
        foreach ( $lines as $nr => $line )
        {
            $height += $line['height'];
        }

        return $height;
    }

    /**
     * Render a single text block into the given area
     *
     * All markup inside of the given string is considered inline markup (in
     * CSS terms). Inline markup should be given as common docbook inline
     * markup, like <emphasis>.
     *
     * Returns a boolean indicator whether the rendering of the full text
     * in the available space succeeded or not.
     *
     * @param ezcDocumentPdfBoundingBox $space
     * @param ezcDocumentPdfHyphenator $hyphenator
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $text
     * @return void
     */
    public function renderBlock( ezcDocumentPdfBoundingBox $space, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $text )
    {
        // Inference page styles
        $styles = $this->styles->inferenceFormattingRules( $text );

        // Iterate over tokens and try to fit them in the current line, use
        // hyphenator to split words.
        $tokens = $this->tokenize( $text, $tokenizer );
        $lines  = $this->fitTokensInLines( $tokens, $hyphenator, $space->width );

        // Try to render text into evaluated box
        return $this->renderTextBox( $lines, $space, $styles );
    }
}
?>
