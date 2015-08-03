<?php
/**
 * File containing the ezcDocumentPdfMediaObjectRenderer class.
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
 * Media object renderer.
 *
 * Renders a media object, an image, at the current text rendering position. 
 * The image is automatically scaled down to the available dimensions.
 *
 * Explicit width and height definitions for the image are not yet taken into 
 * account. The image won't be scaled down explicitely, but it is left to the 
 * driver to handle the ccaling, so that also high resolution images could be 
 * embedded.
 *
 * Also renders an optional image title, if set as a caption in the docbook 
 * source.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfMediaObjectRenderer extends ezcDocumentPdfRenderer
{
    /**
     * Render a media object.
     *
     * @param ezcDocumentPdfPage $page 
     * @param ezcDocumentPdfHyphenator $hyphenator 
     * @param ezcDocumentPdfTokenizer $tokenizer 
     * @param ezcDocumentLocateableDomElement $media 
     * @param ezcDocumentPdfMainRenderer $mainRenderer 
     * @return bool
     */
    public function renderNode( ezcDocumentPdfPage $page, ezcDocumentPdfHyphenator $hyphenator, ezcDocumentPdfTokenizer $tokenizer, ezcDocumentLocateableDomElement $media, ezcDocumentPdfMainRenderer $mainRenderer )
    {
        // Inference page styles
        $styles = $this->styles->inferenceFormattingRules( $media );

        // Locate image file
        $imageData = $media->getElementsByTagName( 'imagedata' )->item( 0 );
        $imageFile = $mainRenderer->locateFile( (string) $imageData->getAttribute( 'fileref' ) );
        $image = ezcDocumentPdfImage::createFromFile( $imageFile );

        // Estimate size of image box
        $width = $this->getMediaBoxWidth( $styles, $page, $image );

        // Get available height with the estimated width
        $dimensions = $this->scaleImage( $styles, $image, $page, $width );
        $switchBack = false;
        if ( ( $space = $page->testFitRectangle( $page->x, $page->y, $dimensions[0], $dimensions[1] ) ) === false )
        {
            // Image with estimated dimensions does not fit on current page any
            // more.
            $page = $mainRenderer->getNextRenderingPosition(
                ( $pWidth = $width->get() ) + $styles['text-column-spacing']->value,
                $pWidth
            );
            $switchBack = true;
        }

        // Get maximum available space
        $space = $page->testFitRectangle( $page->x, $page->y, $width->get(), null );

        // Apply margin of mediaobject
        $space->x      += $styles['margin']->value['left'];
        $space->y      += $styles['margin']->value['top'];
        $space->width  -= $styles['margin']->value['left'] + $styles['margin']->value['right'];
        $space->height -= $styles['margin']->value['top']  + $styles['margin']->value['bottom'];

        // Estimate required height of text blocks
        $captions      = $media->getElementsByTagName( 'caption' );
        $captionHeight = 0;
        $textRenderer  = new ezcDocumentPdfTextBlockRenderer( $this->driver, $this->styles );
        foreach ( $captions as $caption )
        {
            $captionHeight += $textRenderer->estimateHeight( $space->width, $hyphenator, $tokenizer, $caption );
        }

        if ( ( $imageHeight = ( $space->height - $captionHeight ) ) < 0 )
        {
            return false;
        }

        // Reduce the image size, of it does not fit any more because of the captions
        if ( $imageHeight < $dimensions[1] )
        {
            $dimensions[0] *= $imageHeight / $dimensions[1];
            $dimensions[1]  = $imageHeight;
        }

        // Render image
        $this->driver->drawImage(
            $imageFile, $image->getMimeType(),
            $space->x + ( $space->width - $dimensions[0] ) / 2, $space->y,
            $dimensions[0], $dimensions[1]
        );
        $space->y += $dimensions[1];

        // Render captions
        foreach ( $captions as $caption )
        {
            $space->y += $textRenderer->renderBlock( $space, $hyphenator, $tokenizer, $caption );
        }

        // Set covered space covered
        $page->setCovered(
            new ezcDocumentPdfBoundingBox(
                $page->x, $page->y,
                $space->width, $space->y - $page->y
            )
        );
        $page->y = $space->y;

        // Go back to previous page, if requested
        if ( $switchBack )
        {
            $this->driver->goBackOnePage();
        }

        return true;
    }

    /**
     * Calculate width of media box.
     *
     * @param array $styles
     * @param ezcDocumentPdfPage $page
     * @param ezcDocumentPdfImage $image
     * @return ezcDocumentPcssMeasure
     */
    public function getMediaBoxWidth( array $styles, ezcDocumentPdfPage $page, ezcDocumentPdfImage $image )
    {
        if ( $styles['text-columns']->value <= 1 )
        {
            // Just use the full inner width, we do not support floating yet.
            return new ezcDocumentPcssMeasure( $page->innerWidth );
        }

        $imageWidth  = $image->getDimensions();
        $imageWidth  = $imageWidth[0]->get();

        $columns     = $styles['text-columns']->value;
        $spacing     = $styles['text-column-spacing']->value;
        $columnWidth = ( $page->innerWidth - ( $spacing * ( $columns - 1 ) ) ) / $columns;

        $width       = $columnWidth;
        $spanning    = 1;
        while ( ( $imageWidth > $width ) &&
                ( $spanning < $columns ) )
        {
            $width += $columnWidth + $spacing;
            ++$spanning;
        }

        return $this->width = new ezcDocumentPcssMeasure( $width );
    }

    /**
     * Calculate scale of image.
     *
     * Calculates the output size of the image, depending on the available
     * space and the image dimensions.
     *
     * @param array $styles
     * @param ezcDocumentPdfImage $image
     * @param ezcDocumentPdfPage $page
     * @param flaot $width
     * @return array
     */
    protected function scaleImage( array $styles, ezcDocumentPdfImage $image, ezcDocumentPdfPage $page, $width )
    {
        $imageDimensions = $image->getDimensions();

        // Scale image down, if exceeds the maximum available width
        $imageSize = array( $imageDimensions[0]->get(), $imageDimensions[1]->get() );
        if ( $imageSize[0] > $width->get() )
        {
            $imageSize[1] *= $width->get() / $imageSize[0];
            $imageSize[0]  = $width->get();
        }

        // @todo: Apply styles for image scaling.

        // Check if image would fit on a new page using its default size.
        if ( $page->innerHeight > $imageSize[1] )
        {
            return $imageSize;
        }

        // Otherwise we need to scale the image down even further
        $imageSize[0] *= $page->innerHeight / $imageSize[1];
        $imageSize[1]  = $page->innerHeight;
        return $imageSize;
    }
}
?>
