<?php
/**
 * File containing the ezcDocumentOdtMetaGenerator class.
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
 * Generates basic meta data for ODT files.
 *
 * @package Document
 * @access private
 * @version //autogen//
 * @todo Add more and especially configurable meta data.
 * @todo Replace meta data from template on a configurable basis.
 */
class ezcDocumentOdtMetaGenerator
{
    /**
     * Version string.
     *
     * Automatically replaced during release.
     */
    const VERSION = '//autogen//';

    /**
     * Development version string.
     *
     * Used when {@link self::VERSION} is not replaced with a version number.
     */
    const DEV_VERSION = 'dev';

    /**
     * Generator string template. 
     */
    const GENERATOR = 'eZComponents/Document-%s';

    /**
     * Generates basic meta data in $odtDocument.
     * 
     * @param DOMDocument $odtDocument 
     */
    public function generateMetaData( DOMElement $odtMetaSection )
    {
        $this->insertGenerator( $odtMetaSection );
        $this->insertDate( $odtMetaSection );
    }

    /**
     * Inserts the <meta:generator/> tag.
     * 
     * @param DOMElement $metaSection 
     */
    protected function insertGenerator( DOMElement $metaSection )
    {
        $version = ( self::VERSION === '//auto' . 'gen//'
            ? self::DEV_VERSION
            : self::VERSION
        );

        $metaSection->appendChild(
            $metaSection->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_META,
                'meta:generator',
                sprintf( self::GENERATOR, $version )
            )
        );
    }

    /**
     * Inserts <meta:creation-date /> and <dc:date/> tags.
     *
     * Note that OpenOffice.org 3.1.1 is not capable of parsing W3C compliant 
     * dates with TZ offset correctly {@see
     * http://www.openoffice.org/issues/show_bug.cgi?id=107437}. We do not work 
     * around this issue, since it's too minor.
     * 
     * @param DOMElement $metaSection 
     */
    protected function insertDate( DOMElement $metaSection )
    {
        $date       = new DateTime();
        $dateString = $date->format( DateTime::W3C );

        $metaSection->appendChild(
            $metaSection->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_META,
                'meta:creation-date',
                $dateString
            )
        );
        $metaSection->appendChild(
            $metaSection->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_DC,
                'dc:date',
                $dateString
            )
        );
    }
}

?>
