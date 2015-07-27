<?php
/**
 * File containing the ezcDocumentOdtStyleParser class.
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */

/**
 * Parses ODT styles.
 *
 * An instance of this class is used parse style information from an DOMElement 
 * of a ODT document.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtStyleParser
{
    /**
     * Maps list-leve style XML elements to classes.
     *
     * @var array(string=>string)
     */
    protected static $listClassMap = array(
        'list-level-style-number' => 'ezcDocumentOdtListLevelStyleNumber',
        'list-level-style-bullet' => 'ezcDocumentOdtListLevelStyleBullet',
    );

    /**
     * Maps XML attributes to object attributes.
     *
     * @var array
     */
    protected static $listAttributeMap = array(
        'list-level-style-number' => array(
            ezcDocumentOdt::NS_ODT_STYLE => array(
                'num-format' => 'numFormat',
            ),
            ezcDocumentOdt::NS_ODT_TEXT => array(
                'display-levels' => 'displayLevels',
                'start-value'    => 'startValue'
            ),
        ),
        'list-level-style-bullet' => array(
            ezcDocumentOdt::NS_ODT_STYLE => array(
                'num-suffix'  => 'numSuffix',
                'num-prefix'  => 'numPrefix',
            ),
            ezcDocumentOdt::NS_ODT_TEXT => array(
                'bullet-char' => 'bulletChar',
            ),
        ),
    );

    /**
     * Parses the given $odtStyle.
     *
     * Parses the given $odtStyle and returns a style of $family with $name.
     * 
     * @param DOMElement $odtStyle 
     * @param string $family 
     * @param string $name 
     * @return ezcDocumentOdtStyle
     */
    public function parseStyle( DOMElement $odtStyle, $family, $name = null )
    {
        $style = new ezcDocumentOdtStyle( $family, $name );

        foreach ( $odtStyle->childNodes as $child )
        {
            if ( $child->nodeType === XML_ELEMENT_NODE )
            {
                $style->formattingProperties->setProperties(
                    $this->parseProperties( $child )
                );
            }
        }
        return $style;
    }

    /**
     * Parses the given $odtListStyle.
     *
     * Parses the given $odtListStyle and returns a list style with $name.
     * 
     * @param DOMElement $odtListStyle 
     * @param string $name 
     * @return ezcDocumentOdtListStyle
     */
    public function parseListStyle( DOMElement $odtListStyle, $name )
    {
        $listStyle = new ezcDocumentOdtListStyle( $name );

        foreach ( $odtListStyle->childNodes as $child )
        {
            if ( $child->nodeType === XML_ELEMENT_NODE )
            {
                $listLevel = $this->parseListLevel( $child );
                $listStyle->listLevels[$listLevel->level] = $listLevel;
            }
        }

        return $listStyle;
    }

    /**
     * Parses a list level style.
     *
     * Parses the given $listLevelElement and returns a corresponding 
     * list-level style object.
     * 
     * @param DOMElement $listLevelElement 
     * @return ezcDocumentOdtListLevelStyle
     */
    protected function parseListLevel( DOMElement $listLevelElement )
    {
        if ( !isset( self::$listClassMap[$listLevelElement->localName] ) )
        {
            throw new RuntimeException( "Unknown list-level element {$listLevelElement->localName}." );
        }

        $listLevelClass = self::$listClassMap[$listLevelElement->localName];
        $listLevel = new $listLevelClass(
            $listLevelElement->getAttributeNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'level'
            )
        );

        foreach ( self::$listAttributeMap[$listLevelElement->localName] as $ns => $attrs )
        {
            foreach ( $attrs as $xmlAttr => $objAttr )
            {
                if ( $listLevelElement->hasAttributeNS( $ns, $xmlAttr ) )
                {
                    $listLevel->$objAttr = $listLevelElement->getAttributeNS(
                        $ns,
                        $xmlAttr
                    );
                }
            }
        }

        return $listLevel;
    }

    /**
     * Parses the given property.
     * 
     * @param DOMElement $propElement 
     * @return ezcDocumentOdtFormattingProperties
     */
    protected function parseProperties( DOMElement $propElement )
    {
        $props = new ezcDocumentOdtFormattingProperties(
            $propElement->localName
        );
        // @todo: Parse sub-property elements
        foreach ( $propElement->attributes as $attrNode )
        {
            // @todo: Parse property values
            $props[$attrNode->localName] = $attrNode->value;
        }
        return $props;
    }
}

?>
