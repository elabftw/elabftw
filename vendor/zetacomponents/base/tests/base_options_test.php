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

require_once dirname( __FILE__ ) . '/test_options.php';

/**
 * @package Base
 * @subpackage Tests
 */
class ezcBaseOptionsTest extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite("ezcBaseOptionsTest");
    }

    public function testGetAccessFailure()
    {
        $opt = new ezcBaseTestOptions();
        try
        {
            echo $opt->properties;
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            return;
        }
        $this->fail( "ezcBasePropertyNotFoundException not thrown on access to forbidden property \$properties" );
    }

    public function testGetOffsetAccessFailure()
    {
        $opt = new ezcBaseTestOptions();
        try
        {
            echo $opt["properties"];
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            return;
        }
        $this->fail( "ezcBasePropertyNotFoundException not thrown on access to forbidden property \$properties" );
    }

    public function testSetOffsetAccessFailure()
    {
        $opt = new ezcBaseTestOptions();
        try
        {
            $opt["properties"] = "foo";
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            return;
        }
        $this->fail( "ezcBasePropertyNotFoundException not thrown on access to forbidden property \$properties" );
    }

    public function testConstructorWithParameters()
    {
        $options = new ezcBaseTestOptions( array( 'foo' => 'xxx' ) );
        $this->assertEquals( 'xxx', $options->foo );
    }

    public function testMerge()
    {
        $options = new ezcBaseTestOptions();
        $this->assertEquals( 'bar', $options->foo );
        $options->merge( array( 'foo' => 'xxx' ) );
        $this->assertEquals( 'xxx', $options->foo );
    }

    public function testOffsetExists()
    {
        $options = new ezcBaseTestOptions();
        $this->assertEquals( true, $options->offsetExists( 'foo' ) );
        $this->assertEquals( false, $options->offsetExists( 'bar' ) );
    }

    public function testOffsetSet()
    {
        $options = new ezcBaseTestOptions();
        $this->assertEquals( 'bar', $options->foo );
        $options->offsetSet( 'foo', 'xxx' );
        $this->assertEquals( 'xxx', $options->foo );
    }

    public function testOffsetUnset()
    {
        $options = new ezcBaseTestOptions();
        $this->assertEquals( 'bar', $options->foo );
        $options->offsetUnset( 'foo' );
        $this->assertEquals( null, $options->foo );
        $this->assertEquals( true, $options->offsetExists( 'foo' ) );
    }

    public function testAutoloadOptions()
    {
        $options = new ezcBaseAutoloadOptions();

        try
        {
            $options->no_such_property = 'value';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $options->preload = 'wrong value';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBaseValueException $e )
        {
            $this->assertEquals( "The value 'wrong value' that you were trying to assign to setting 'preload' is invalid. Allowed values are: bool.", $e->getMessage() );
        }
    }

    public function testIterator()
    {
        $options = new ezcBaseTestOptions();

        $expectedArray = array( "foo" => "bar", "baz" => "blah" );

        $resultArray = array();

        foreach( $options as $key => $option )
        {
            $resultArray[$key] = $option;
        }

        $this->assertEquals( $expectedArray, $resultArray );
    }
}

?>