<?php
/**
 * File containing the ezcDocumentPdfPart class
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
 * Abstract base class for additional PDF parts
 *
 * Parts can be new elements in a PDF page, which can hook into the rendering
 * of the PDF page, like footers or headers.
 *
 * This abstract part abse class offers a list of hooks which will be called,
 * if an instance of this class is registered in the renderer, these hooks are:
 *
 * - hookPageCreation
 * - hookPageRendering
 * - hookDocumentCreation
 * - hookDocumentRendering
 *
 * All these hooks do nothing by default, and should be overwritten to
 * accomplish the desired functionality.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentPdfPart
{
    /**
     * Reference to main renderer
     *
     * @var ezcDocumentPdfMainRenderer
     */
    protected $renderer;

    /**
     * Reference to driver
     *
     * @var ezcDocumentPdfDriver
     */
    protected $driver;

    /**
     * Reference to style inferencer
     *
     * @var ezcDocumentPcssStyleInferencer
     */
    protected $styles;

    /**
     * Registration function called by the renderer.
     *
     * Function called by the renderer, to set its properties, which pass the
     * relevant state objects to the part.
     *
     * @param ezcDocumentPdfMainRenderer $renderer
     * @param ezcDocumentPdfDriver $driver
     * @param ezcDocumentPcssStyleInferencer $styles
     * @return void
     */
    public function registerContext( ezcDocumentPdfMainRenderer $renderer, ezcDocumentPdfDriver $driver, ezcDocumentPcssStyleInferencer $styles )
    {
        $this->renderer = $renderer;
        $this->driver   = $driver;
        $this->styles   = $styles;
    }

    /**
     * Hook on page creation
     *
     * Hook called on page creation, so that certain areas might be reserved or
     * it already may render stuff on the frshly created page.
     *
     * @param ezcDocumentPdfPage $page
     * @return void
     */
    public function hookPageCreation( ezcDocumentPdfPage $page )
    {
    }

    /**
     * Hook on page rendering
     *
     * Hook called on page rendering, which means, that a page is complete, by
     * all knowledge of the main renderer.
     *
     * @param ezcDocumentPdfPage $page
     * @return void
     */
    public function hookPageRendering( ezcDocumentPdfPage $page )
    {
    }

    /**
     * Hook on document creation
     *
     * Hook called when a new document is created.
     *
     * @param ezcDocumentLocateableDomElement $element
     * @return void
     */
    public function hookDocumentCreation( ezcDocumentLocateableDomElement $element )
    {
    }

    /**
     * Hook on document rendering
     *
     * Hook called once a document is completely rendered.
     *
     * @return void
     */
    public function hookDocumentRendering()
    {
    }
}
?>
