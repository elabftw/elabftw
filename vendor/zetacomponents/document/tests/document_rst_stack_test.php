<?php
/**
 * ezcDocumentRstStackTests
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
class ezcDocumentRstStackTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testUnShift()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );
    }

    public function testDoubleUnShift()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_unshift( $array, 23 ),
            $stack->unshift( 23 )
        );
    }

    public function testEmptyShift()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );
    }

    public function testShift()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );
    }

    public function testDoubleShift()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_unshift( $array, 23 ),
            $stack->unshift( 23 )
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );
    }

    public function testCountEmpty()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            count( $array ),
            $stack->count()
        );
    }

    public function testCountSingle()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );
    }

    public function testCountDouble()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_unshift( $array, 23 ),
            $stack->unshift( 23 )
        );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );
    }

    public function testCountDoubleReduced()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_unshift( $array, 23 ),
            $stack->unshift( 23 )
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );
    }

    public function testCountDoubleReducedTwice()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_unshift( $array, 23 ),
            $stack->unshift( 23 )
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );
    }

    public function testCountDoubleReducedTriple()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            array_unshift( $array, 1 ),
            $stack->unshift( 1 )
        );

        $this->assertSame(
            array_unshift( $array, 23 ),
            $stack->unshift( 23 )
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );
    }

    public function testPrepend()
    {
        $stack = new ezcDocumentRstStack();
        $stack->unshift( 1 );
        $array = array( 1 );

        $stack->prepend( $prepend = array( 23, 42 ) );
        $array = array_merge( $prepend, $array );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            count( $array ),
            $stack->count()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );

        $this->assertSame(
            array_shift( $array ),
            $stack->shift()
        );
    }

    public function testArrayAccessGet()
    {
        $stack = new ezcDocumentRstStack();
        $stack->unshift( 1 );
        $array = array( 1 );

        $stack->prepend( $prepend = array( 23, 42 ) );
        $array = array_merge( $prepend, $array );

        $this->assertSame(
            $array[0],
            $stack[0]
        );

        $this->assertSame(
            $array[1],
            $stack[1]
        );

        $this->assertSame(
            $array[2],
            $stack[2]
        );
    }

    public function testArrayAccessIsset()
    {
        $stack = new ezcDocumentRstStack();
        $stack->unshift( 1 );
        $array = array( 1 );

        $stack->prepend( $prepend = array( 23, 42 ) );
        $array = array_merge( $prepend, $array );

        $this->assertSame(
            isset( $array[-1] ),
            isset( $stack[-1] )
        );

        $this->assertSame(
            isset( $array[0] ),
            isset( $stack[0] )
        );

        $this->assertSame(
            isset( $array[1] ),
            isset( $stack[1] )
        );

        $this->assertSame(
            isset( $array[2] ),
            isset( $stack[2] )
        );

        $this->assertSame(
            isset( $array[3] ),
            isset( $stack[3] )
        );
    }

    public function testRewindEmpty()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        $this->assertSame(
            reset( $array ),
            $stack->rewind()
        );
    }

    public function testRewind()
    {
        $stack = new ezcDocumentRstStack();
        $stack->unshift( 1 );
        $array = array( 1 );

        $this->assertSame(
            reset( $array ),
            $stack->rewind()
        );
    }

    public function testRewindDouble()
    {
        $stack = new ezcDocumentRstStack();
        $array = array();

        array_unshift( $array, 1 );
        array_unshift( $array, 23 );

        $stack->unshift( 1 );
        $stack->unshift( 23 );

        $this->assertSame(
            reset( $array ),
            $stack->rewind()
        );
    }
}

?>
