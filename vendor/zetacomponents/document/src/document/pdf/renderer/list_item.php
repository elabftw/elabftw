<?php
/**
 * File containing the ezcDocumentPdfListItemRenderer class.
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
 * Renders a list item.
 *
 * Tries to render a list item into the available space, and aborts if
 * not possible.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfListItemRenderer extends ezcDocumentPdfBlockRenderer
{
    /**
     * Item generator used for this list.
     * 
     * @var ezcDocumentListItemGenerator
     */
    protected $generator;

    /**
     * Item number of current item in list.
     * 
     * @var int
     */
    protected $item;

    /**
     * Construct from item number.
     * 
     * @param ezcDocumentPdfDriver $driver
     * @param ezcDocumentPcssStyleInferencer $styles
     * @param ezcDocumentListItemGenerator $generator 
     * @param int $item 
     * @return void
     */
    public function __construct( ezcDocumentPdfDriver $driver, ezcDocumentPcssStyleInferencer $styles, ezcDocumentListItemGenerator $generator, $item )
    {
        parent::__construct( $driver, $styles );
        $this->generator = $generator;
        $this->item      = $item;
    }

    /**
     * Process to render block contents
     * 
     * @param ezcDocumentPdfPage $page 
     * @param ezcDocumentPdfHyphenator $hyphenator 
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $block 
     * @param ezcDocumentPdfMainRenderer $mainRenderer 
     * @return void
     */
    protected function process( ezcDocumentPdfPage $page, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $block, ezcDocumentPdfMainRenderer $mainRenderer )
    {
        // Render list item
        if ( ( $listItem = $this->generator->getListItem( $this->item ) ) !== '' )
        {
            $styles = $this->styles->inferenceFormattingRules( $block );
            $this->driver->drawWord(
                $page->x + $page->xOffset - $styles['padding']->value['left'],
                $page->y + $styles['font-size']->value,
                $listItem
            );
        }

        // Render list contents
        $mainRenderer->process( $block );
    }
}

?>
