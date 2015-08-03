<?php
/**
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package Base
 * @subpackage Tests
 */

/**
 * @package Base
 * @subpackage Tests
 */
class ezcBaseFileRemoveRecursiveTest extends ezcTestCase
{
    protected function setUp()
    {
        $this->tempDir = $this->createTempDir( 'ezcBaseFileRemoveFileRecursiveTest' );
        mkdir( $this->tempDir . '/dir1' );
        mkdir( $this->tempDir . '/dir2' );
        mkdir( $this->tempDir . '/dir2/dir1' );
        mkdir( $this->tempDir . '/dir2/dir1/dir1' );
        mkdir( $this->tempDir . '/dir2/dir2' );
        mkdir( $this->tempDir . '/dir4' );
        mkdir( $this->tempDir . '/dir5' );
        mkdir( $this->tempDir . '/dir6' );
        mkdir( $this->tempDir . '/dir7' );
        mkdir( $this->tempDir . '/dir7/dir1' );
        mkdir( $this->tempDir . '/dir8' );
        mkdir( $this->tempDir . '/dir8/dir1' );
        mkdir( $this->tempDir . '/dir8/dir1/dir1' );
        file_put_contents( $this->tempDir . '/dir1/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir1/file2.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir1/.file3.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir2/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir2/dir1/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir2/dir1/dir1/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir2/dir1/dir1/file2.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir2/dir2/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir4/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir4/file2.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir5/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir5/file2.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir6/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir6/file2.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir7/dir1/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir8/dir1/file1.txt', 'test' );
        file_put_contents( $this->tempDir . '/dir8/dir1/dir1/file1.txt', 'test' );
        chmod( $this->tempDir . '/dir4/file1.txt', 0 );
        chmod( $this->tempDir . '/dir5', 0 );
        chmod( $this->tempDir . '/dir6', 0400 );
        chmod( $this->tempDir . '/dir7', 0500 );
        chmod( $this->tempDir . '/dir8/dir1', 0500 );
    }

    protected function tearDown()
    {
        chmod( $this->tempDir . '/dir5', 0700 );
        chmod( $this->tempDir . '/dir6', 0700 );
        chmod( $this->tempDir . '/dir7', 0700 );
        chmod( $this->tempDir . '/dir8/dir1', 0700 );
        $this->removeTempDir();
    }

    public function testRecursive1()
    {
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        ezcBaseFile::removeRecursive( $this->tempDir . '/dir1' );
        self::assertEquals( 12, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        ezcBaseFile::removeRecursive( $this->tempDir . '/dir2' );
        self::assertEquals( 7, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
    }

    public function testRecursive2()
    {
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        try
        {
            ezcBaseFile::removeRecursive( $this->tempDir . '/dir3' );
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            self::assertEquals( "The directory file '{$this->tempDir}/dir3' could not be found.", $e->getMessage() );
        }
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
    }

    public function testRecursive3()
    {
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        try
        {
            ezcBaseFile::removeRecursive( $this->tempDir . '/dir4' );
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            self::assertEquals( "The file '{$this->tempDir}/dir5' can not be opened for reading.", $e->getMessage() );
        }
        self::assertEquals( 13, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
    }

    public function testRecursive4()
    {
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        try
        {
            ezcBaseFile::removeRecursive( $this->tempDir . '/dir5' );
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            self::assertEquals( "The file '{$this->tempDir}/dir5' can not be opened for reading.", $e->getMessage() );
        }
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
    }

    public function testRecursive5()
    {
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        try
        {
            ezcBaseFile::removeRecursive( $this->tempDir . '/dir6' );
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            // Make no asumption on which file is tried to be removed first
            self::assertEquals(
                1,
                preg_match(
                    "(The file '{$this->tempDir}/dir6/file[12].txt' can not be removed.)",
                    $e->getMessage()
                )
            );
        }
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
    }

    public function testRecursiveNotWritableParent()
    {
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
        try
        {
            ezcBaseFile::removeRecursive( $this->tempDir . '/dir7/dir1' );
        }
        catch ( ezcBaseFilePermissionException $e )
        {
            self::assertEquals( "The file '{$this->tempDir}/dir7' can not be opened for writing.", $e->getMessage() );
        }
        self::assertEquals( 15, count( ezcBaseFile::findRecursive( $this->tempDir ) ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcBaseFileRemoveRecursiveTest" );
    }
}
?>
