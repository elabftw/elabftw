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
class ezcBaseStructTest extends ezcTestCase
{
    public function testBaseStructGetSet()
    {
        $struct = new ezcBaseStruct();

        try
        {
            $struct->no_such_property = 'value';
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }

        try
        {
            $value = $struct->no_such_property;
            $this->fail( 'Expected exception was not thrown.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        {
            $this->assertEquals( "No such property name 'no_such_property'.", $e->getMessage() );
        }
    }

    public function testBaseRepositoryDirectorySetState()
    {
        $dir = ezcBaseRepositoryDirectory::__set_state( array( 'type' => ezcBaseRepositoryDirectory::TYPE_EXTERNAL, 'basePath' => '/tmp', 'autoloadPath' => '/tmp/autoload' ) );
        $this->assertEquals( ezcBaseRepositoryDirectory::TYPE_EXTERNAL, $dir->type );
        $this->assertEquals( '/tmp', $dir->basePath );
        $this->assertEquals( '/tmp/autoload', $dir->autoloadPath );
    }

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( "ezcBaseStructTest" );
    }
}
?>
