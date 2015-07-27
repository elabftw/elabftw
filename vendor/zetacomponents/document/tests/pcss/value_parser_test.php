<?php
/**
 * ezcDocumentPdfStyleInferenceTests
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
class ezcDocumentPcssValueParserTests extends ezcTestCase
{
    protected $document;
    protected $xpath;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getMeasureBoxValues()
    {
        return array(
            array(
                "11",
                array(
                    'top'    => 11.,
                    'right'  => 11.,
                    'bottom' => 11.,
                    'left'   => 11.,
                ),
                '11.00mm 11.00mm 11.00mm 11.00mm',
            ),
            array(
                "11pt",
                array(
                    'top'    => 3.9,
                    'right'  => 3.9,
                    'bottom' => 3.9,
                    'left'   => 3.9,
                ),
                '3.88mm 3.88mm 3.88mm 3.88mm',
            ),
            array(
                "11 12",
                array(
                    'top'    => 11.,
                    'right'  => 12.,
                    'bottom' => 11.,
                    'left'   => 12.,
                ),
                '11.00mm 12.00mm 11.00mm 12.00mm',
            ),
            array(
                "11\t \r \n \t12",
                array(
                    'top'    => 11.,
                    'right'  => 12.,
                    'bottom' => 11.,
                    'left'   => 12.,
                ),
                '11.00mm 12.00mm 11.00mm 12.00mm',
            ),
            array(
                "11 12 13",
                array(
                    'top'    => 11.,
                    'right'  => 12.,
                    'bottom' => 13.,
                    'left'   => 12.,
                ),
                '11.00mm 12.00mm 13.00mm 12.00mm',
            ),
            array(
                "11 12 13 14",
                array(
                    'top'    => 11.,
                    'right'  => 12.,
                    'bottom' => 13.,
                    'left'   => 14.,
                ),
                '11.00mm 12.00mm 13.00mm 14.00mm'
            ),
            array(
                "11mm 12in 13px 14pt",
                array(
                    'top'    => 11.,
                    'right'  => 304.8,
                    'bottom' => 4.6,
                    'left'   => 4.94,
                ),
                '11.00mm 304.80mm 4.59mm 4.94mm',
            ),
        );
    }

    /**
     * @dataProvider getMeasureBoxValues
     */
    public function testMeasureBoxValueHandler( $input, $expectation, $string )
    {
        $value = new ezcDocumentPcssStyleMeasureBoxValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid box measures read.', .1
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid measure box string serialization.'
        );
    }

    public static function getColorValues()
    {
        return array(
            array(
                "#000000",
                array(
                    'red'   => 0.,
                    'green' => 0.,
                    'blue'  => 0.,
                    'alpha' => 0.,
                ),
                '#000000',
            ),
            array(
                "#ffffff",
                array(
                    'red'   => 1.,
                    'green' => 1.,
                    'blue'  => 1.,
                    'alpha' => 0.,
                ),
                '#ffffff',
            ),
            array(
                "#babdb6",
                array(
                    'red'   => .73,
                    'green' => .74,
                    'blue'  => .71,
                    'alpha' => 0.,
                ),
                '#babdb6',
            ),
            array(
                "#babdb6b0",
                array(
                    'red'   => .73,
                    'green' => .74,
                    'blue'  => .71,
                    'alpha' => .69,
                ),
                '#babdb6b0',
            ),
            array(
                "#BABDB6",
                array(
                    'red'   => .73,
                    'green' => .74,
                    'blue'  => .71,
                    'alpha' => 0.,
                ),
                '#babdb6',
            ),
            array(
                "#000",
                array(
                    'red'   => 0.,
                    'green' => 0.,
                    'blue'  => 0.,
                    'alpha' => 0.,
                ),
                '#000000',
            ),
            array(
                "#fff",
                array(
                    'red'   => 1.,
                    'green' => 1.,
                    'blue'  => 1.,
                    'alpha' => 0.,
                ),
                '#ffffff',
            ),
            array(
                "#bad",
                array(
                    'red'   => .73,
                    'green' => .67,
                    'blue'  => .87,
                    'alpha' => 0.,
                ),
                '#bbaadd',
            ),
            array(
                "#bad6",
                array(
                    'red'   => .73,
                    'green' => .67,
                    'blue'  => .87,
                    'alpha' => .4,
                ),
                '#bbaadd66',
            ),
            array(
                "#BAD",
                array(
                    'red'   => .73,
                    'green' => .67,
                    'blue'  => .87,
                    'alpha' => 0.,
                ),
                '#bbaadd',
            ),
            array(
                "rgb( 0, 255, 9823 )",
                array(
                    'red'   => .0,
                    'green' => 1.,
                    'blue'  => .37,
                    'alpha' => 0.,
                ),
                '#00ff5f',
            ),
            array(
                "   RGB     ( 0 , 10 , 127 ) ",
                array(
                    'red'   => .0,
                    'green' => .04,
                    'blue'  => .5,
                    'alpha' => 0.,
                ),
                '#000a7f',
            ),
            array(
                "rgba( 0, 255, 1023, 127 )",
                array(
                    'red'   => .0,
                    'green' => 1.,
                    'blue'  => 1.,
                    'alpha' => .5,
                ),
                '#00ffff7f',
            ),
            array(
                "   RGBA     ( 12 , 23 , 1023 , 12 ) ",
                array(
                    'red'   => .05,
                    'green' => .1,
                    'blue'  => 1.,
                    'alpha' => .05,
                ),
                '#0c17ff0c',
            ),
            array(
                "transparent",
                array(
                    'red'   => 0.,
                    'green' => 0.,
                    'blue'  => 0.,
                    'alpha' => 1.,
                ),
                '#000000ff',
            ),
            array(
                "none",
                array(
                    'red'   => 0.,
                    'green' => 0.,
                    'blue'  => 0.,
                    'alpha' => 1.,
                ),
                '#000000ff',
            ),
        );
    }

    /**
     * @dataProvider getColorValues
     */
    public function testColorValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleColorValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid color values read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid color string serialization.'
        );
    }

    public function testInvalidColorSpecification()
    {
        try {
            $value = new ezcDocumentPcssStyleColorValue();
            $value->parse( 'something invalid' );
            $this->fail( 'Expected ezcDocumentParserException.' );
        } catch ( ezcDocumentParserException $e )
        { /* Expected */ }
    }

    public static function getLineStyleValues()
    {
        return array(
            array(
                "solid",
                "solid",
                'solid',
            ),
            array(
                " dotted ",
                'dotted',
                'dotted',
            ),
            array(
                "\t\ngroove\r",
                'groove',
                'groove',
            ),
        );
    }

    /**
     * @dataProvider getLineStyleValues
     */
    public function testLineValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleLineValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid style style value read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid style style string serialization.'
        );
    }

    public static function getBorderStyleValues()
    {
        return array(
            array(
                "1mm",
                array(
                    'width' => 1,
                    'style'  => 'solid',
                    'color' => array(
                        'red'   => 1.,
                        'green' => 1.,
                        'blue'  => 1.,
                        'alpha' => 0.,
                    ),
                ),
                '1.00mm solid #ffffff',
            ),
            array(
                "dashed",
                array(
                    'width' => 0,
                    'style'  => 'dashed',
                    'color' => array(
                        'red'   => 1.,
                        'green' => 1.,
                        'blue'  => 1.,
                        'alpha' => 0.,
                    ),
                ),
                '0.00mm dashed #ffffff',
            ),
            array(
                "rgb( 255, 0, 0 )",
                array(
                    'width' => 0,
                    'style'  => 'solid',
                    'color' => array(
                        'red'   => 1.,
                        'green' => 0.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                ),
                '0.00mm solid #ff0000',
            ),
            array(
                "1pt #F00",
                array(
                    'width' => .35,
                    'style'  => 'solid',
                    'color' => array(
                        'red'   => 1.,
                        'green' => 0.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                ),
                '0.35mm solid #ff0000',
            ),
            array(
                "1 inset #0f0",
                array(
                    'width' => 1.,
                    'style'  => 'inset',
                    'color' => array(
                        'red'   => 0.,
                        'green' => 1.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                ),
                '1.00mm inset #00ff00',
            ),
        );
    }

    /**
     * @dataProvider getBorderStyleValues
     */
    public function testBorderValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleBorderValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid border style value read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid border style string serialization.'
        );
    }

    public static function getColorBoxStyleValues()
    {
        return array(
            array(
                "#0f0 #ff0000 rgb( 0, 0, 255 )",
                array(
                    'top' => array(
                        'red'   => 0.,
                        'green' => 1.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                    'right' => array(
                        'red'   => 1.,
                        'green' => 0.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                    'bottom' => array(
                        'red'   => 0.,
                        'green' => 0.,
                        'blue'  => 1.,
                        'alpha' => 0.,
                    ),
                    'left' => array(
                        'red'   => 1.,
                        'green' => 0.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                ),
                '#00ff00 #ff0000 #0000ff #ff0000',
            ),
            array(
                "#fF0",
                array(
                    'top' => array(
                        'red'   => 1.,
                        'green' => 1.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                    'right' => array(
                        'red'   => 1.,
                        'green' => 1.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                    'bottom' => array(
                        'red'   => 1.,
                        'green' => 1.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                    'left' => array(
                        'red'   => 1.,
                        'green' => 1.,
                        'blue'  => 0.,
                        'alpha' => 0.,
                    ),
                ),
                '#ffff00 #ffff00 #ffff00 #ffff00',
            ),
        );
    }

    /**
     * @dataProvider getColorBoxStyleValues
     */
    public function testColorBoxValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleColorBoxValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid color value read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid color box string serialization.'
        );
    }

    public static function getLineBoxStyleValues()
    {
        return array(
            array(
                "solid double outset",
                array(
                    'top'    => 'solid',
                    'right'  => 'double',
                    'bottom' => 'outset',
                    'left'   => 'double',
                ),
                'solid double outset double',
            ),
            array(
                "inset",
                array(
                    'top'    => 'inset',
                    'right'  => 'inset',
                    'bottom' => 'inset',
                    'left'   => 'inset',
                ),
                'inset inset inset inset',
            ),
        );
    }

    /**
     * @dataProvider getLineBoxStyleValues
     */
    public function testLineBoxValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleLineBoxValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid style value read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid style box string serialization.'
        );
    }

    public static function getBorderBoxStyleValues()
    {
        return array(
            array(
                "1 inset #0f0",
                array(
                    'top' => array(
                        'width' => 1.,
                        'style'  => 'inset',
                        'color' => array(
                            'red'   => 0.,
                            'green' => 1.,
                            'blue'  => 0.,
                            'alpha' => 0.,
                        ),
                    ),
                    'right' => array(
                        'width' => 1.,
                        'style'  => 'inset',
                        'color' => array(
                            'red'   => 0.,
                            'green' => 1.,
                            'blue'  => 0.,
                            'alpha' => 0.,
                        ),
                    ),
                    'bottom' => array(
                        'width' => 1.,
                        'style'  => 'inset',
                        'color' => array(
                            'red'   => 0.,
                            'green' => 1.,
                            'blue'  => 0.,
                            'alpha' => 0.,
                        ),
                    ),
                    'left' => array(
                        'width' => 1.,
                        'style'  => 'inset',
                        'color' => array(
                            'red'   => 0.,
                            'green' => 1.,
                            'blue'  => 0.,
                            'alpha' => 0.,
                        ),
                    ),
                ),
                '1.00mm inset #00ff00 1.00mm inset #00ff00 1.00mm inset #00ff00 1.00mm inset #00ff00',
            ),
            array(
                "1mm #fF0 outset 2mm",
                array(
                    'top' => array(
                        'width' => 1.,
                        'style'  => 'solid',
                        'color' => array(
                            'red'   => 1.,
                            'green' => 1.,
                            'blue'  => 0.,
                            'alpha' => 0.,
                        ),
                    ),
                    'right' => array(
                        'width' => 0.,
                        'style'  => 'outset',
                        'color' => array(
                            'red'   => 1.,
                            'green' => 1.,
                            'blue'  => 1.,
                            'alpha' => 0.,
                        ),
                    ),
                    'bottom' => array(
                        'width' => 2.,
                        'style'  => 'solid',
                        'color' => array(
                            'red'   => 1.,
                            'green' => 1.,
                            'blue'  => 1.,
                            'alpha' => 0.,
                        ),
                    ),
                    'left' => array(
                        'width' => 0.,
                        'style'  => 'outset',
                        'color' => array(
                            'red'   => 1.,
                            'green' => 1.,
                            'blue'  => 1.,
                            'alpha' => 0.,
                        ),
                    ),
                ),
                '1.00mm solid #ffff00 0.00mm outset #ffffff 2.00mm solid #ffffff 0.00mm outset #ffffff',
            ),
        );
    }

    /**
     * @dataProvider getBorderBoxStyleValues
     */
    public function testBorderBoxValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleBorderBoxValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid border style value read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid border style string serialization.'
        );
    }

    public static function getUrlStyleValues()
    {
        return array(
            array(
                "url( foo.ttf )",
                array(
                    "foo.ttf"
                ),
                "url( foo.ttf )",
            ),
            array(
                "url(foo.ttf),local(font.pfb),url(/some/../path/to/font.foo)",
                array(
                    "foo.ttf",
                    "font.pfb",
                    "/some/../path/to/font.foo",
                ),
                "url( foo.ttf ), url( font.pfb ), url( /some/../path/to/font.foo )",
            ),
        );
    }

    /**
     * @dataProvider getUrlStyleValues
     */
    public function testSrcValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleSrcValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Invalid src style value read.', .01
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid src style string serialization.'
        );
    }

    public static function getListStyleValues()
    {
        return array(
            array(
                'single',
                array( 'single' ),
                'single'
            ),
            array(
                'single     ',
                array( 'single' ),
                'single'
            ),
            array(
                'first second',
                array( 'first', 'second' ),
                'first second'
            ),
            array(
                'first     second   ',
                array( 'first', 'second' ),
                'first second'
            ),
            array(
                '    first              second    ',
                array( 'first', 'second' ),
                'first second'
            ),
            array(
                '    first              second                  third',
                array( 'first', 'second', 'third' ),
                'first second third'
            ),
        );
    }

    /**
     * @dataProvider getListStyleValues
     */
    public function testListValueHandler( $input, $expectation, $string = '' )
    {
        $value = new ezcDocumentPcssStyleListValue();
        $value->parse( $input );

        $this->assertEquals(
            $expectation,
            $value->value,
            'Incorrect list value read.'
        );

        $this->assertEquals(
            $string,
            (string) $value,
            'Invalid list value string serialization.'
        );
    }
}

?>
