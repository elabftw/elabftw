<?php
/**
 * File containing the ezcDocumentPdfHeaderPdfPart class.
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
 * Just an alias for the footer class, but will be positioned on the
 * top of a page by default.
 *
 * A header, or any other PDF part, can be registered for rendering in the main
 * PDF class using the registerPdfPart() method, like:
 *
 * <code>
 *  $pdf = new ezcDocumentPdf();
 *
 *  // Add a customized footer
 *  $pdf->registerPdfPart( new ezcDocumentPdfHeaderPdfPart(
 *      new ezcDocumentPdfFooterOptions( array( 
 *          'showPageNumber' => false,
 *          'height'         => '10mm',
 *      ) )
 *  ) );
 *
 *  $pdf->createFromDocbook( $docbook );
 *  file_put_contents( __FILE__ . '.pdf', $pdf );
 * </code>
 *
 * Since it is just an alias class for the
 * ezcDocumentPdfFooterPdfPart it is also confugured by using the
 * ezcDocumentPdfFooterOptions class.
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentPdfHeaderPdfPart extends ezcDocumentPdfFooterPdfPart
{
    /**
     * Create a new footer PDF part.
     *
     * @param ezcDocumentPdfFooterOptions $options 
     */
    public function __construct( ezcDocumentPdfFooterOptions $options = null )
    {
        $this->options = ( $options === null ?
            new ezcDocumentPdfFooterOptions() :
            $options );
        $this->options->footer = false;
    }
}
?>
