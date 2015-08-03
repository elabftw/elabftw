<?php
/**
 * File containing the ezcDocumentPdfImage class
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
 * PDF image handler
 *
 * Class handling image references, extracting their mime type and dimensions.
 * Dispatches to concrete handler, which claim that they are able to handle the 
 * current image and requests dimensions and mime type from the handler.
 *
 * By default only one handler is registered, which uses PHPs getimagesize() 
 * function to handle all images, which getimagesize() can handle. For other 
 * image types additional handlers can be registered using the registerImageHander() 
 * method.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfImage
{
    /**
     * List of registered image handlers
     */
    protected static $handler = array();

    /**
     * Path to image file
     *
     * @var string
     */
    protected $file;

    /**
     * Handler used for the current image file.
     *
     * @var ezcDocumentPdfImageHandler
     */
    protected $currentHandler;

    /**
     * Construct new image handler
     *
     * @return void
     */
    public function __construct()
    {
        self::$handler = array(
            new ezcDocumentPdfPhpImageHandler(),
        );
    }

    /**
     * Create image handler object from file
     *
     * @param string $file
     * @return ezcDocumentPdfImage
     */
    public static function createFromFile( $file )
    {
        $image = new ezcDocumentPdfImage();
        $image->loadFile( $file );
        return $image;
    }

    /**
     * Register additional image handler
     *
     * @param ezcDocumentPdfImageHandler $handler
     * @return void
     */
    public static function registerImageHander( ezcDocumentPdfImageHandler $handler )
    {
        self::$handler[] = $handler;
    }

    /**
     * Load image file
     *
     * @param string $file
     * @return void
     */
    public function loadFile( $file )
    {
        $this->file = $file;

        foreach ( self::$handler as $handler )
        {
            if ( $handler->canHandle( $file ) )
            {
                $this->currentHandler = $handler;
                $this->file           = $file;
                return true;
            }
        }

        throw new ezcBaseFunctionalityNotSupportedException( $file, 'Unhandled file type' );
    }

    /**
     * Get image dimensions
     *
     * Return an array with the image dimensions. The array will look like:
     * array( ezcDocumentPcssMeasure $width, ezcDocumentPcssMeasure $height ).
     *
     * @return array
     */
    public function getDimensions()
    {
        return $this->currentHandler->getDimensions( $this->file );
    }

    /**
     * Get image mime type
     *
     * Return a string with the image mime type, identifying the type of the
     * image.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->currentHandler->getMimeType( $this->file );
    }
}
?>
