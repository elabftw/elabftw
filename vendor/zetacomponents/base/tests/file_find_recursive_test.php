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
class ezcBaseFileFindRecursiveTest extends ezcTestCase
{
    public function testRecursive1()
    {
        $expected = array(
            0 => 'src/base.php',
            1 => 'src/base_autoload.php',
            2 => 'src/exceptions/autoload.php',
            3 => 'src/exceptions/double_class_repository_prefix.php',
            4 => 'src/exceptions/exception.php',
            5 => 'src/exceptions/extension_not_found.php',
            6 => 'src/exceptions/file_exception.php',
            7 => 'src/exceptions/file_io.php',
            8 => 'src/exceptions/file_not_found.php',
            9 => 'src/exceptions/file_permission.php',
            10 => 'src/exceptions/functionality_not_supported.php',
            11 => 'src/exceptions/init_callback_configured.php',
            12 => 'src/exceptions/invalid_callback_class.php',
            13 => 'src/exceptions/invalid_parent_class.php',
            14 => 'src/exceptions/property_not_found.php',
            15 => 'src/exceptions/property_permission.php',
            16 => 'src/exceptions/setting_not_found.php',
            17 => 'src/exceptions/setting_value.php',
            18 => 'src/exceptions/value.php',
            19 => 'src/exceptions/whatever.php',
            20 => 'src/ezc_bootstrap.php',
            21 => 'src/features.php',
            22 => 'src/file.php',
            23 => 'src/init.php',
            24 => 'src/interfaces/configuration_initializer.php',
            25 => 'src/interfaces/exportable.php',
            26 => 'src/interfaces/persistable.php',
            27 => 'src/metadata.php',
            28 => 'src/metadata/pear.php',
            29 => 'src/metadata/tarball.php',
            30 => 'src/options.php',
            31 => 'src/options/autoload.php',
            32 => 'src/struct.php',
            33 => 'src/structs/file_find_context.php',
            34 => 'src/structs/repository_directory.php',
        );

        $files = ezcBaseFile::findRecursive( "src", array(), array( '@/docs/@', '@svn@', '@\.swp$@', '@git@' ), $stats );
        self::assertEquals( $expected, $files );
        self::assertEquals( array( 'size' => 134176, 'count' => 35 ), $stats );
    }

    public function testRecursive2()
    {
        $expected = array(
            0 => 'vendor/zetacomponents/unit-test/CREDITS',
            1 => 'vendor/zetacomponents/unit-test/ChangeLog',
            2 => 'vendor/zetacomponents/unit-test/NOTICE',
            3 => 'vendor/zetacomponents/unit-test/composer.json',
            4 => 'vendor/zetacomponents/unit-test/composer.lock',
            5 => 'vendor/zetacomponents/unit-test/design/class_diagram.png',
            6 => 'vendor/zetacomponents/unit-test/src/constraint/image.php',
            7 => 'vendor/zetacomponents/unit-test/src/regression_suite.php',
            8 => 'vendor/zetacomponents/unit-test/src/regression_test.php',
            9 => 'vendor/zetacomponents/unit-test/src/test/case.php',
            10 => 'vendor/zetacomponents/unit-test/src/test/image_case.php',
            11 => 'vendor/zetacomponents/unit-test/src/test_autoload.php',
        );

        self::assertEquals( $expected, ezcBaseFile::findRecursive( "vendor/zetacomponents/unit-test", array( '@^vendor/zetacomponents/unit-test/@' ), array( '@/docs/@', '@\.git@', '@\.swp$@' ), $stats ) );
        self::assertEquals( array( 'size' => 191117, 'count' => 12 ), $stats );
    }

    public function testRecursive3()
    {
        $expected = array (
            0 => 'vendor/zetacomponents/unit-test/design/class_diagram.png',
        );
        self::assertEquals( $expected, ezcBaseFile::findRecursive( "vendor/zetacomponents/unit-test", array( '@\.png$@' ), array( '@\.svn@' ), $stats ) );
        self::assertEquals( array( 'size' => 166066, 'count' => 1 ), $stats );
    }

    public function testRecursive4()
    {
        $expected = array (
            0 => 'vendor/zetacomponents/unit-test/design/class_diagram.png',
        );
        self::assertEquals( $expected, ezcBaseFile::findRecursive( "vendor/zetacomponents/unit-test", array( '@/design/@' ), array( '@\.svn@' ), $stats ) );
        self::assertEquals( array( 'size' => 166066, 'count' => 1 ), $stats );
    }

    public function testRecursive5()
    {
        $expected = array (
            0 => 'vendor/zetacomponents/unit-test/design/class_diagram.png',
            1 => 'vendor/zetacomponents/unit-test/src/constraint/image.php',
            2 => 'vendor/zetacomponents/unit-test/src/regression_suite.php',
            3 => 'vendor/zetacomponents/unit-test/src/regression_test.php',
            4 => 'vendor/zetacomponents/unit-test/src/test/case.php',
            5 => 'vendor/zetacomponents/unit-test/src/test/image_case.php',
            6 => 'vendor/zetacomponents/unit-test/src/test_autoload.php',
        );
        self::assertEquals( $expected, ezcBaseFile::findRecursive( "vendor/zetacomponents/unit-test", array( '@\.(php|png)$@' ), array( '@/docs/@', '@\.svn@' ) ) );
    }

    public function testRecursive6()
    {
        $expected = array();
        self::assertEquals( $expected, ezcBaseFile::findRecursive( "vendor/zetacomponents/unit-test", array( '@xxx@' ) ) );
    }

    public function testNonExistingDirectory()
    {
        $expected = array();
        try
        {
            ezcBaseFile::findRecursive( "NotHere", array( '@xxx@' ) );
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            self::assertEquals( "The directory file 'NotHere' could not be found.", $e->getMessage() );
        }
    }

    public function testStatsEmptyArray()
    {
        $expected = array (
            0 => 'vendor/zetacomponents/unit-test/design/class_diagram.png',
        );

        $stats = array();
        self::assertEquals( $expected, ezcBaseFile::findRecursive( "vendor/zetacomponents/unit-test", array( '@/design/@' ), array( '@\.svn@' ), $stats ) );
        self::assertEquals( array( 'size' => 166066, 'count' => 1 ), $stats );
    }

    public static function suite()
    {
         return new PHPUnit_Framework_TestSuite( "ezcBaseFileFindRecursiveTest" );
    }
}
?>
