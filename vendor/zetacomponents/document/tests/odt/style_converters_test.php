<?php
/**
 * ezcDocumentOdtFormattingPropertiesTest.
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
class ezcDocumentOdtPcssConvertersTest extends ezcTestCase
{
    protected $domElement;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function setUp()
    {
        $domDocument = new DOMDocument();
        $this->domElement = $domDocument->appendChild(
            $domDocument->createElement( 'parent' )
        );
    }

    protected function assertAttributesCorrect( array $expectedAttributes )
    {
        $this->assertEquals(
            count( $expectedAttributes ),
            $this->domElement->attributes->length,
            'Inconsistent number of text property element attributes.'
        );

        foreach ( $expectedAttributes as $attrDef )
        {
            $this->assertTrue(
                $this->domElement->hasAttributeNS(
                    $attrDef[0],
                    $attrDef[1]
                ),
                "Missing attribute '{$attrDef[0]}:{$attrDef[1]}'."
            );
            $this->assertEquals(
                $attrDef[2],
                ( $actAttrVal = $this->domElement->getAttributeNS(
                    $attrDef[0],
                    $attrDef[1]
                ) ),
                "Attribute '{$attrDef[0]}:{$attrDef[1]}' has incorrect value '$actAttrVal', expected '{$attrDef[2]}'."
            );
        }
    }

    /**
     * @dataProvider getTextDecorationTestSets
     */
    public function testConvertTextDecoration( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssTextDecorationConverter();
        $converter->convert( $this->domElement, 'text-decoration', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    /**
     * Test sets for the 'text-decoration' style attribute.
     */
    public static function getTextDecorationTestSets()
    {
        return array(
            'line-through' => array(
                // style
                new ezcDocumentPcssStyleListValue( array( 'line-through' ) ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-line-through-type', 'single' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-line-through-style', 'solid' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-line-through-width', 'auto' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-line-through-color', 'font-color' ),
                )
            ),
            'underline' => array(
                // style
                new ezcDocumentPcssStyleListValue( array( 'underline' ) ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-type', 'single' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-style', 'solid' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-width', 'auto' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-color', 'font-color' ),
                )
            ),
            'overline' => array(
                // style
                new ezcDocumentPcssStyleListValue( array( 'overline' ) ),
                // expected attributes
                array(
                )
            ),
            'blink' => array(
                // style
                new ezcDocumentPcssStyleListValue( array( 'blink' ) ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-blinking', 'true' ),
                )
            ),
            'multiple' => array(
                // style
                new ezcDocumentPcssStyleListValue( array( 'blink', 'underline' ) ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-blinking', 'true' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-type', 'single' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-style', 'solid' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-width', 'auto' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'text-underline-color', 'font-color' ),
                )
            ),
        );
    }

    /**
     * @dataProvider getColorTestSets
     */
    public function testConvertColor( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssColorConverter();
        $converter->convert( $this->domElement, 'color', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    /**
     * Test sets for color style attributes.
     */
    public static function getColorTestSets()
    {
        return array(
            'non-transparent' => array(
                // style
                new ezcDocumentPcssStyleColorValue(
                    array(
                        'red'   => 1.0,
                        'green' => 1.0,
                        'blue'  => 1.0,
                        'alpha' => 0.4,
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'color', '#ffffff' ),
                )
            ),
            'transparent' => array(
                // style
                new ezcDocumentPcssStyleColorValue(
                    array(
                        'red'   => 1.0,
                        'green' => 1.0,
                        'blue'  => 1.0,
                        'alpha' => 0.5,
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'color', 'transparent' ),
                )
            ),
            'value' => array(
                // style
                new ezcDocumentPcssStyleColorValue(
                    array(
                        'red'   => 0.75294117647059,
                        'green' => 1.0,
                        'blue'  => 0,
                        'alpha' => 0.0,
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'color', '#c0ff00' ),
                )
            ),
        );
    }

    /**
     * @dataProvider getBackgroundColorTestSets
     */
    public function testConvertBackgroundColor( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssColorConverter();
        $converter->convert( $this->domElement, 'background-color', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    /**
     * Test sets for background-color style attributes.
     */
    public static function getBackgroundColorTestSets()
    {
        // Re-use color test sets, but with background-color attribute name
        $colorTestSets = self::getColorTestSets();
        foreach ( $colorTestSets as $setId => $set )
        {
            foreach( $set[1] as $attrId => $attrDef )
            {
                $attrDef[1] = 'background-color';
                $colorTestSets[$setId][1][$attrId] = $attrDef;
            }
        }
        return $colorTestSets;
    }

    /**
     * @dataProvider getFontSizeTestSets
     */
    public function testConvertFontSize( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssFontSizeConverter();
        $converter->convert( $this->domElement, 'font-size', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    /**
     * Test sets for font style attributes.
     */
    public static function getFontSizeTestSets()
    {
        return array(
            'font-size' => array(
                // styles
                new ezcDocumentPcssStyleMeasureValue( 23 ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'font-size', '23mm' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'font-size-asian', '23mm' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'font-size-complex', '23mm' ),
                )
            ),
        );
    }

    /**
     * @dataProvider getTextFontNameTestSets
     */
    public function testConvertMiscFontProperty( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssFontNameConverter();
        $converter->convert( $this->domElement, 'font-name', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    public static function getTextFontNameTestSets()
    {
        return array(
            'font-name' => array(
                // styles
                new ezcDocumentPcssStyleStringValue( 'DejaVu Sans' ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'font-name', 'DejaVu Sans' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'font-name-asian', 'DejaVu Sans' ),
                    array( ezcDocumentOdt::NS_ODT_STYLE, 'font-name-complex', 'DejaVu Sans' ),
                )
            ),
        );
    }

    /**
     * @dataProvider getTextAlignTestSets
     */
    public function testConvertMiscProperty( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtDefaultPcssConverter();
        $converter->convert( $this->domElement, 'text-align', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    public static function getTextAlignTestSets()
    {
        return array(
            array(
                // style
                new ezcDocumentPcssStyleStringValue( 'center' ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'text-align', 'center' ),
                )
            ),
        );
    }

    /**
     * @dataProvider getMarginTestSets
     */
    public function testConvertMarginProperty( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssMarginConverter();
        $converter->convert( $this->domElement, 'margin', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    /**
     * Test sets for the 'margin' style attribute.
     */
    public static function getMarginTestSets()
    {
        return array(
            'margin full' => array(
                // style
                new ezcDocumentPcssStyleMeasureBoxValue(
                    array(
                        'top'    => 1,
                        'left'   => 2,
                        'bottom' => 3,
                        'right'  => 4
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-top', '1mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-left', '2mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-bottom', '3mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-right', '4mm' ),
                )
            ),
            'margin missings' => array(
                // style
                new ezcDocumentPcssStyleMeasureBoxValue(
                    array(
                        'top'    => 1,
                        'right'  => 4
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-top', '1mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-right', '4mm' ),
                )
            ),
            'margin empty' => array(
                // style
                new ezcDocumentPcssStyleMeasureBoxValue(
                    array(
                        'top'    => 1,
                        'left'   => 0,
                        'bottom' => 3,
                        'right'  => null
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-top', '1mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-left', '0mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-bottom', '3mm' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'margin-right', '0mm' ),
                )
            ),
       );
    }

    /**
     * @dataProvider getBorderTestSets
     */
    public function testConvertBorderProperty( $styleValue, $expectedAttributes )
    {
        $converter = new ezcDocumentOdtPcssBorderConverter();
        $converter->convert( $this->domElement, 'border', $styleValue );

        $this->assertAttributesCorrect(
            $expectedAttributes
        );
    }

    /**
     * Test sets for the 'margin' style attribute.
     */
    public static function getBorderTestSets()
    {
        return array(
            'border full' => array(
                // style
                new ezcDocumentPcssStyleBorderBoxValue(
                    array(
                        'top' => array(
                            'width' => 1,
                            'style' => 'solid',
                            'color' => array(
                                'red'   => 1,
                                'green' => 0,
                                'blue'  => 0,
                                'alpha' => 0
                            )
                        ),
                        'left' => array(
                            'width' => 10,
                            'style' => 'solid',
                            'color' => array(
                                'red'   => 0,
                                'green' => 1,
                                'blue'  => 0,
                                'alpha' => 0
                            )
                        ),
                        'bottom' => array(
                            'width' => 1,
                            'style' => 'solid',
                            'color' => array(
                                'red'   => 0,
                                'green' => 0,
                                'blue'  => 1,
                                'alpha' => .8
                            )
                        ),
                        'right' => array(
                            'width' => 1,
                            'style' => 'dotted',
                            'color' => array(
                                'red'   => .3,
                                'green' => .2,
                                'blue'  => .4,
                                'alpha' => .2
                            )
                        ),
                    )
                ),
                // expected attributes
                array(
                    // NS, attribute name, value
                    array( ezcDocumentOdt::NS_ODT_FO, 'border-top', '1mm solid #ff0000' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'border-left', '10mm solid #00ff00' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'border-bottom', '1mm solid transparent' ),
                    array( ezcDocumentOdt::NS_ODT_FO, 'border-right', '1mm dotted #4d3366' ),
                )
            ),
       );
    }

}

?>
