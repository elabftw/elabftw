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
class ezcBaseFileCalculateRelativePathTest extends ezcTestCase
{
    public function testRelative1()
    {
        $result = ezcBaseFile::calculateRelativePath( 'C:/foo/1/2/php.php', 'C:/foo/bar' );
        self::assertEquals( '..' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '2' . DIRECTORY_SEPARATOR . 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:/foo/bar/php.php', 'C:/foo/bar' );
        self::assertEquals( 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:/php.php', 'C:/foo/bar/1/2');
        self::assertEquals( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:/bar/php.php', 'C:/foo/bar/1/2');
        self::assertEquals('..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bar' . DIRECTORY_SEPARATOR . 'php.php', $result);
    }

    public function testRelative2()
    {
        $result = ezcBaseFile::calculateRelativePath( 'C:\\foo\\1\\2\\php.php', 'C:\\foo\\bar' );
        self::assertEquals( '..' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '2' . DIRECTORY_SEPARATOR . 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:\\foo\\bar\\php.php', 'C:\\foo\\bar' );
        self::assertEquals( 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:\\foo\\bar', 'C:\\foo\\bar\\php.php' );
        self::assertEquals( '..', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:\\php.php', 'C:\\foo\\bar\\1\\2');
        self::assertEquals( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( 'C:\\bar\\php.php', 'C:\\foo\\bar\\1\\2');
        self::assertEquals('..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bar' . DIRECTORY_SEPARATOR . 'php.php', $result);

        $result = ezcBaseFile::calculateRelativePath( 'C:\\bar\\php.php', 'D:\\foo\\bar\\1\\2');
        self::assertEquals('..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'C:' . DIRECTORY_SEPARATOR . 'bar' . DIRECTORY_SEPARATOR . 'php.php', $result);
    }

    public function testRelative3()
    {
        $result = ezcBaseFile::calculateRelativePath( '/foo/1/2/php.php', '/foo/bar' );
        self::assertEquals( '..' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR . '2' . DIRECTORY_SEPARATOR . 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( '/foo/bar/php.php', '/foo/bar' );
        self::assertEquals( 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( '/foo/bar', '/foo/bar/php.php' );
        self::assertEquals( '..', $result );

        $result = ezcBaseFile::calculateRelativePath( '/foo/', '/foo/bar/php.php' );
        self::assertEquals( '../..', $result );

        $result = ezcBaseFile::calculateRelativePath( '/foo', '/foo/bar/php.php' );
        self::assertEquals( '../..', $result );

        $result = ezcBaseFile::calculateRelativePath( '/', '/foo/bar/php.php' );
        self::assertEquals( '../../..', $result );

        $result = ezcBaseFile::calculateRelativePath( '/php.php', '/foo/bar/1/2');
        self::assertEquals( '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'php.php', $result );

        $result = ezcBaseFile::calculateRelativePath( '/bar/php.php', '/foo/bar/1/2');
        self::assertEquals('..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bar' . DIRECTORY_SEPARATOR . 'php.php', $result);
    }

    // test for issue #13370
    public function testEqual()
    {
        self::assertEquals( '.', ezcBaseFile::calculateRelativePath( '/bar/php.php', '/bar/php.php' ) );
        self::assertEquals( '.', ezcBaseFile::calculateRelativePath( 'C:\workspace\xxx_upgrade', 'C:\workspace\xxx_upgrade' ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcBaseFileCalculateRelativePathTest" );
    }
}
?>
