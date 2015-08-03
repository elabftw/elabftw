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
class ezcBaseFileIsAbsoluteTest extends ezcTestCase
{
    public static function testAbsoluteWindows1()
    {
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\\winnt\\winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\winnt\winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\\winnt', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\\winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\\winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:\table.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:winnt', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c\\winnt.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\winnt.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\winnt.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'winnt.sys', 'Windows' ) );

        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\server\share\foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\server\share\foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\tequila\share\foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\tequila\share\foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\tequila\thare\foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\tequila\thare\foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\server\\share\foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\server\\share\foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\tequila\\share\foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\tequila\\share\foo.sys', 'Windows' ) );

        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\etc\init.d\apache', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '\\etc\\init.d\\apache', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc\init.d\apache', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc\\init.d\\apache', 'Windows' ) );
    }

    public static function testAbsoluteWindows2()
    {
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt//winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:/winnt/winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:/winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:/table.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:winnt', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c//winnt.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//winnt.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '/winnt.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'winnt.sys', 'Windows' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//server/share/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////server/share/foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/share/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////tequila/share/foo.sys', 'Windows' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/thare/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////tequila/thare/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//server//share/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////server//share/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//tequila//share/foo.sys', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////tequila//share/foo.sys', 'Windows' ) );

        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '/etc/init.d/apache', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//etc//init.d//apache', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc/init.d/apache', 'Windows' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc//init.d//apache', 'Windows' ) );
    }

    public static function testAbsoluteWindows3()
    {
        if ( ezcBaseFeatures::os() !== 'Windows' )
        {
            self::markTestSkipped( 'Test is for Windows only' );
        }

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt//winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:/winnt/winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:/winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c://winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'c:/table.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:winnt' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c//winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '/winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'winnt.sys' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//server/share/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////server/share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/share/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////tequila/share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/thare/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////tequila/thare/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//server//share/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////server//share/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//tequila//share/foo.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '////tequila//share/foo.sys' ) );

        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '/etc/init.d/apache' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( '//etc//init.d//apache' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc/init.d/apache' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc//init.d//apache' ) );
    }

    public static function testAbsoluteLinux1()
    {
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\\winnt\\winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\winnt\winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\\winnt', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\\winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\\winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:\table.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:winnt', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c\\winnt.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\winnt.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'winnt.sys', 'Linux' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\server\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\server\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\tequila\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\tequila\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\tequila\thare\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\tequila\thare\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\server\\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\server\\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\tequila\\share\foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\\\tequila\\share\foo.sys', 'Linux' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\etc\init.d\apache', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '\\etc\\init.d\\apache', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc\init.d\apache', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc\\init.d\\apache', 'Linux' ) );
    }

    public static function testAbsoluteLinux2()
    {
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt//winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:/winnt/winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:/winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:/table.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:winnt', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c//winnt.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//winnt.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '/winnt.sys', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'winnt.sys', 'Linux' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//server/share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////server/share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////tequila/share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/thare/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////tequila/thare/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//server//share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////server//share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila//share/foo.sys', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////tequila//share/foo.sys', 'Linux' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '/etc/init.d/apache', 'Linux' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//etc//init.d//apache', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc/init.d/apache', 'Linux' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc//init.d//apache', 'Linux' ) );
    }

    public static function testAbsoluteLinux3()
    {
        if ( ezcBaseFeatures::os() === 'Windows' )
        {
            self::markTestSkipped( 'Test is for unix-like systems only' );
        }

        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt//winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:/winnt/winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:/winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c://winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:/table.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c:winnt' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'c//winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//winnt.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '/winnt.sys' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'winnt.sys' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//server/share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////server/share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////tequila/share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila/thare/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////tequila/thare/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//server//share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////server//share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//tequila//share/foo.sys' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '////tequila//share/foo.sys' ) );

        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '/etc/init.d/apache' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( '//etc//init.d//apache' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc/init.d/apache' ) );
        self::assertEquals( false, ezcBaseFile::isAbsolutePath( 'etc//init.d//apache' ) );
    }

    public static function testAbsoluteStreamWrapper()
    {
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'phar://test.phar/foo' ) );
        self::assertEquals( true, ezcBaseFile::isAbsolutePath( 'http://example.com/file' ) );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcBaseFileIsAbsoluteTest" );
    }
}
?>
