<?php
/**
 * File containing the ezcDocumentPdfImageHandler class
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
 * Abstract base class for image handlers. Should be extended by classes, which
 * can handle a set of image types and provide information about image mime
 * types and dimensions.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
abstract class ezcDocumentPdfImageHandler
{
    /**
     * Can this handler handle the passed image?
     *
     * Returns a boolean value indicatin whether the current handler can handle
     * the passed image file.
     *
     * @param string $file
     * @return bool
     */
    abstract public function canHandle( $file );

    /**
     * Get image dimensions
     *
     * Return an array with the image dimensions. The array will look like:
     * array( ezcDocumentPcssMeasure $width, ezcDocumentPcssMeasure $height ).
     *
     * @param string $file
     * @return array
     */
    abstract public function getDimensions( $file );

    /**
     * Get image mime type
     *
     * Return a string with the image mime type, identifying the type of the
     * image.
     *
     * @param string $file
     * @return string
     */
    abstract public function getMimeType( $file );
}
?>
