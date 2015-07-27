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

require_once 'init/base_init_callback.php';
require_once 'init/base_init_class.php';

/**
 * @package Base
 * @subpackage Tests
 */
class ezcBaseInitTest extends ezcTestCase
{
    public function setUp()
    {
        testBaseInitClass::$instance = null;
    }

    public function testCallbackWithClassThatDoesNotExists()
    {
        try
        {
            ezcBaseInit::setCallback( 'testBaseInit', 'classDoesNotExist' );
            $this->fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseInitInvalidCallbackClassException $e )
        {
            $this->assertEquals( "Class 'classDoesNotExist' does not exist, or does not implement the 'ezcBaseConfigurationInitializer' interface.", $e->getMessage() );
        }
    }

    public function testCallbackWithClassThatDoesNotImplementTheInterface()
    {
        try
        {
            ezcBaseInit::setCallback( 'testBaseInit', 'ezcBaseFeatures' );
            $this->fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseInitInvalidCallbackClassException $e )
        {
            $this->assertEquals( "Class 'ezcBaseFeatures' does not exist, or does not implement the 'ezcBaseConfigurationInitializer' interface.", $e->getMessage() );
        }
    }

    public function testCallback1()
    {
        $obj = testBaseInitClass::getInstance();
        $this->assertEquals( false, $obj->configured );
    }

    public function testCallback2()
    {
        ezcBaseInit::setCallback( 'testBaseInit', 'testBaseInitCallback' );
        $obj = testBaseInitClass::getInstance();
        $this->assertEquals( true, $obj->configured );
    }
    
    public function testCallback3()
    {
        try
        {
            ezcBaseInit::setCallback( 'testBaseInit', 'testBaseInitCallback' );
            $this->fail( "Expected exception not thrown." );
        }
        catch ( ezcBaseInitCallbackConfiguredException $e )
        {
            $this->assertEquals( "The 'testBaseInit' is already configured with callback class 'testBaseInitCallback'.", $e->getMessage() );
        }
    }

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite("ezcBaseInitTest");
    }
}
?>
