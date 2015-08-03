<?php
/**
 * ezcDocumentConverterEzp3TpEzp4Tests
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
 * @subpackage Tests
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentWikiTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testReadCreoleDocument()
    {
        $wiki = new ezcDocumentCreoleWiki();
        $wiki->loadFile( dirname( __FILE__ ) . '/files/wiki/creole/s_008_paragraphs.txt' );

        $docbook = $wiki->getAsDocbook();

        $this->assertEquals(
            file_get_contents( dirname( __FILE__ ) . '/files/wiki/creole/s_008_paragraphs.xml' ),
            $docbook->save(),
            'Document not visited as expected.'
        );
    }

    public function testWriteCreoleDocument()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/files/wiki/creole/s_008_paragraphs.xml' );

        $wiki = new ezcDocumentCreoleWiki();
        $wiki->createFromDocbook( $docbook );

        $this->assertEquals(
            file_get_contents( dirname( __FILE__ ) . '/files/wiki/creole/s_008_paragraphs.txt' ),
            $wiki->save(),
            'Document not visited as expected.'
        );
    }

    public function testReadDokuwikiDocument()
    {
        $wiki = new ezcDocumentDokuwikiWiki();
        $wiki->loadFile( dirname( __FILE__ ) . '/files/wiki/dokuwiki/s_001_inline_markup.txt' );

        $docbook = $wiki->getAsDocbook();

        $this->assertEquals(
            file_get_contents( dirname( __FILE__ ) . '/files/wiki/dokuwiki/s_001_inline_markup.xml' ),
            $docbook->save(),
            'Document not visited as expected.'
        );
    }

    public function testWriteDokuwikiDocument()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/files/wiki/dokuwiki/s_001_inline_markup.xml' );

        try
        {
            $wiki = new ezcDocumentDokuwikiWiki();
            $wiki->createFromDocbook( $docbook );
            $wiki->save();
            $this->fail( 'Expected ezcDocumentMissingVisitorException' );
        }
        catch ( ezcDocumentMissingVisitorException $e )
        { /* Expected */ }
    }

    public function testReadConfluenceDocument()
    {
        $wiki = new ezcDocumentConfluenceWiki();
        $wiki->loadFile( dirname( __FILE__ ) . '/files/wiki/confluence/s_002_inline_markup.txt' );

        $docbook = $wiki->getAsDocbook();

        $this->assertEquals(
            file_get_contents( dirname( __FILE__ ) . '/files/wiki/confluence/s_002_inline_markup.xml' ),
            $docbook->save(),
            'Document not visited as expected.'
        );
    }

    public function testWriteConfluenceDocument()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/files/wiki/confluence/s_002_inline_markup.xml' );

        try
        {
            $wiki = new ezcDocumentConfluenceWiki();
            $wiki->createFromDocbook( $docbook );
            $wiki->save();
            $this->fail( 'Expected ezcDocumentMissingVisitorException' );
        }
        catch ( ezcDocumentMissingVisitorException $e )
        { /* Expected */ }
    }
}

?>
