<?php
/**
 * File containing the ezcDocumentPdfPhpImageHandler class
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
 * PHP image handler
 *
 * Basic image handler which can detect mime type and dimensions of some images 
 * using the PHP function getimagesize(). It therefore can analyse all images 
 * covered by the PHP function, which is available by default.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPdfPhpImageHandler extends ezcDocumentPdfImageHandler
{
    /**
     * Cache for extracted image information
     *
     * @var array
     */
    protected $cache;

    /**
     * Can this handler handle the passed image?
     *
     * Returns a boolean value indicatin whether the current handler can handle
     * the passed image file.
     *
     * @param string $file
     * @return bool
     */
    public function canHandle( $file )
    {
        if ( isset( $this->cache[$file] ) )
        {
            return true;
        }

        if ( ( $data = getimagesize( $file ) ) === false )
        {
            return false;
        }

        // If width or height is not available this is not a simple
        // image type, which we can handle.
        if ( !$data[0] || !$data[1] )
        {
            return false;
        }

        $this->cache[$file] = array(
            'dimensions' => array(
                new ezcDocumentPcssMeasure( $data[0] . 'px' ),
                new ezcDocumentPcssMeasure( $data[1] . 'px' ),
            ),
            'mimetype'   => $data['mime'],
        );
        return true;
    }

    /**
     * Get image dimensions
     *
     * Return an array with the image dimensions. The array will look like:
     * array( ezcDocumentPcssMeasure $width, ezcDocumentPcssMeasure $height ).
     *
     * @param string $file
     * @return array
     */
    public function getDimensions( $file )
    {
        if ( !isset( $this->cache[$file] ) &&
             !$this->canHandle( $file ) )
        {
            return false;
        }

        return $this->cache[$file]['dimensions'];
    }

    /**
     * Get image mime type
     *
     * Return a string with the image mime type, identifying the type of the
     * image.
     *
     * @param string $file
     * @return string
     */
    public function getMimeType( $file )
    {
        if ( !isset( $this->cache[$file] ) &&
             !$this->canHandle( $file ) )
        {
            return false;
        }

        return $this->cache[$file]['mimetype'];
    }
}
?>
