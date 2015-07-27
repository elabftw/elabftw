<?php
/**
 * ezcDocTestConvertXhtmlDocbook
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
abstract class ezcDocumentOptionsTestCase extends ezcTestCase
{
    /**
     * Return class name of options class to test
     * 
     * @return string
     */
    abstract protected function getOptionsClassName();

    /**
     * Return default values for the options
     *
     * Returned array should be in the format:
     *
     * <code>
     *  array(
     *      array(
     *          'optionname',
     *          $value,
     *      ),
     *      ...
     *  )
     * </code>
     * 
     * @return array
     */
    public static function provideDefaultValues()
    {
        return array();
    }

    /**
     * Return valid data for options to test
     *
     * Returned array should be in the format:
     *
     * <code>
     *  array(
     *      array(
     *          'optionname',
     *          array(
     *              'value 1', 'value 2', ...
     *          ),
     *      ),
     *      ...
     *  )
     * </code>
     * 
     * @return array
     */
    public static function provideValidData()
    {
        return array();
    }

    /**
     * Return invalid data for options to test
     *
     * Returned array should be in the format:
     *
     * <code>
     *  array(
     *      array(
     *          'optionname',
     *          array(
     *              'value 1', 'value 2', ...
     *          ),
     *      ),
     *      ...
     *  )
     * </code>
     * 
     * @return array
     */
    public static function provideInvalidData()
    {
        return array();
    }

    /**
     * Test all options provided by the data provider
     * 
     * @dataProvider provideDefaultValues
     */
    public function testOptionsDefaultValues( $property, $value )
    {
        $class = $this->getOptionsClassName();
        $option = new $class();

        if ( is_object( $option->$property ) )
        {
            $this->assertEquals(
                $value,
                $option->$property,
                "Default value in option class '$class' of property '$property' is wrong."
            );
        }
        else
        {
            $this->assertSame(
                $value,
                $option->$property,
                "Default value in option class '$class' of property '$property' is not '$value'."
            );
        }
    }

    /**
     * Test all options provided by the data provider
     * 
     * @dataProvider provideValidData
     */
    public function testOptionsValidValues( $property, $values )
    {
        $class = $this->getOptionsClassName();
        $option = new $class();

        $this->assertSetProperty(
            $option,
            $property,
            $values
        );
    }

    /**
     * Test all options provided by the data provider
     * 
     * @dataProvider provideInvalidData
     */
    public function testOptionsInvalidValues( $property, $values )
    {
        $class = $this->getOptionsClassName();
        $option = new $class();

        $this->assertSetPropertyFails(
            $option,
            $property,
            $values
        );
    }

    public function testUnknownValue()
    {
        $class = $this->getOptionsClassName();
        $option = new $class();

        try
        {
            $option->get_an_not_existing_property;
            $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        { /* Expected */ }

        try
        {
            $option->get_an_not_existing_property = true;
            $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        { /* Expected */ }
    }
}

?>
