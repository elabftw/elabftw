<?php
/**
 * File containing the ezcDocumentOdtImageLocator class.
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
 * Class to locate images in DocBook to ODT conversion.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentOdtImageLocator
{
    /**
     * Paths to search for images.
     * 
     * @var array(string)
     */
    protected $paths = array();

    /**
     * Creates a new image locator for the given $document.
     * 
     * @param ezcDocument $document 
     */
    public function __construct( ezcDocument $document )
    {
        $this->paths[] = $document->getPath();

        if ( ( $workDir = getcwd() ) !== false )
        {
            $this->paths[] = $workDir;
        }

        $this->paths[] = sys_get_temp_dir();
    }

    /**
     * Returns the realpath of the given image $fileName.
     *
     * Tries to find the image for the given $fileName in the file system and 
     * returns its realpath, if found. If the image cannot be located, false is 
     * returned.
     * 
     * @param string $fileName 
     * @return string|false
     */
    public function locateImage( $fileName )
    {
        if ( file_exists( $fileName ) )
        {
            return realpath( $fileName );
        }

        if ( substr( $fileName, 0, 1 ) === DIRECTORY_SEPARATOR )
        {
            // File name is absolute, but image does not exist
            return false;
        }

        foreach ( $this->paths as $path )
        {
            if ( file_exists( ( $imgPath = $path . DIRECTORY_SEPARATOR . $fileName ) ) )
            {
                return realpath( $imgPath );
            }
        }
        return false;
    }
}

?>
