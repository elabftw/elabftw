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
 * @package Base
 * @subpackage Tests
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
/**
 * @package Base
 * @subpackage Tests
 */
class ezcBaseFeaturesUnixTest extends ezcTestCase
{
    protected function setUp()
    {
        $uname = php_uname( 's' );
        if ( substr( $uname, 0, 7 ) == 'Windows' )
        {
            $this->markTestSkipped( 'Unix tests' );
        }
    }

    public function testSupportsLink()
    {
        $this->assertEquals( true, ezcBaseFeatures::supportsLink() );
    }

    public function testSupportsSymLink()
    {
        $this->assertEquals( true, ezcBaseFeatures::supportsSymLink() );
    }

    public function testSupportsUserId()
    {
        $this->assertEquals( true, ezcBaseFeatures::supportsUserId() );
    }

/*  // Need to find a way to make this test work, as setting global enviroment variables
    // is not working (putenv( "PATH=" ) doesn't unset $_ENV["PATH"])
    // One solution would be to use in the ezcBaseFeatures::getPath():
    // getenv( 'PATH' ) instead of $_ENV['PATH'] (but that won't work under IIS).
    public function testHasImageIdentifyNoPath()
    {
        $envPath = getenv( 'PATH' );
        putenv( "PATH=" );
        $this->assertEquals( false, ezcBaseFeatures::hasImageIdentify() );
        putenv( "PATH={$envPath}" );
    }
*/

    public function testHasImageConvert()
    {
        $this->assertEquals( true, ezcBaseFeatures::hasImageConvert() );
    }

    public function testGetImageConvertExecutable()
    {
        $this->assertEquals( '/usr/bin/convert', ezcBaseFeatures::getImageConvertExecutable() );
    }

    public function testGetImageIdentifyExecutable()
    {
        $this->assertEquals( '/usr/bin/identify', ezcBaseFeatures::getImageIdentifyExecutable() );
    }

    public function testHasImageIdentify()
    {
        $this->assertEquals( true, ezcBaseFeatures::hasImageIdentify() );
    }

    public function testHasExtensionSupport1()
    {
        $this->assertEquals( true, ezcBaseFeatures::hasExtensionSupport( 'standard' ) );
    }

    public function testHasExtensionSupportNotFound1()
    {
        $this->assertEquals( false, ezcBaseFeatures::hasExtensionSupport( 'non_existent_extension' ) );
        try
        {
            throw new ezcBaseExtensionNotFoundException( 'non_existent_extension', null, 'This is just a test.' );
        }
        catch ( ezcBaseExtensionNotFoundException $e )
        {
            $this->assertEquals( "The extension 'non_existent_extension' could not be found. This is just a test.",
                                 $e->getMessage() );
        }
    }

    public function testHasExtensionSupportNotFound2()
    {
        $this->assertEquals( false, ezcBaseFeatures::hasExtensionSupport( 'non_existent_extension' ) );
        try
        {
            throw new ezcBaseExtensionNotFoundException( 'non_existent_extension', '1.2', 'This is just a test.' );
        }
        catch ( ezcBaseExtensionNotFoundException $e )
        {
            $this->assertEquals( "The extension 'non_existent_extension' with version '1.2' could not be found. This is just a test.",
                                 $e->getMessage() );
        }
    }

    public function testHasFunction1()
    {
        $this->assertEquals( true, ezcBaseFeatures::hasFunction( 'function_exists' ) );
    }

    public function testHasFunction2()
    {
        $this->assertEquals( false, ezcBaseFeatures::hasFunction( 'non_existent_function_in_php' ) );
    }

    public function testHasExtensionSupport2()
    {
        $this->assertEquals( true, ezcBaseFeatures::hasExtensionSupport( 'date', '5.1.0' ) );
    }

    public function testClassExists()
    {
        $this->assertEquals( true, ezcBaseFeatures::classExists( 'Exception', false ) );
    }

    public function testClassExistsAutoload()
    {
        $this->assertEquals( true, ezcBaseFeatures::classExists( 'ezcBaseFeatures' ) );
    }

    public function testClassExistsNotFound()
    {
        $this->assertEquals( false, ezcBaseFeatures::classExists( 'ezcBaseNonExistingClass', false ) );
    }

    public function testClassExistsNotFoundAutoload()
    {
        $this->assertEquals( false, ezcBaseFeatures::classExists( 'ezcBaseNonExistingClass' ) );
    }

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite(__CLASS__);
    }
}
?>
