<?php
/**
 * File containing the abstract ezcDocumentOdtStyleGenerator base class.
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
 * Base class for style generators.
 *
 * Style generators used in {@link ezcDocumentOdtStyler} must extend this 
 * abstract class.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
abstract class ezcDocumentOdtStyleGenerator
{
    /**
     * Style converters. 
     * 
     * @var ezcDocumentOdtPcssConverterManager
     */
    protected $styleConverters;

    /**
     * Counters for style prefixes. 
     * 
     * @var array(string=>int)
     */
    protected $prefixCounters = array();

    /**
     * Creates a new style genertaor.
     * 
     * @param ezcDocumentOdtPcssConverterManager $styleConverters 
     */
    public function __construct( ezcDocumentOdtPcssConverterManager $styleConverters )
    {
        $this->styleConverters = $styleConverters;
    }

    /**
     * Returns if a style generator handles style generation for $odtElement.
     * 
     * @param DOMElement $odtElement 
     * @return bool
     */
    public abstract function handles( DOMElement $odtElement );

    /**
     * Creates the necessary styles to apply $styleAttributes to $odtElement.
     *
     * This method should create the necessary styles to apply $styleAttributes 
     * to the given $odtElement. In addition, it must set the correct 
     * attributes on $odtElement to source this style.
     * 
     * @param ezcDocumentOdtStyleInformation $styleInfo 
     * @param DOMElement $odtElement 
     * @param array $styleAttributes 
     */
    public abstract function createStyle( ezcDocumentOdtStyleInformation $styleInfo, DOMElement $odtElement, array $styleAttributes );

    /**
     * Returns a unique style name with the given $prefix.
     *
     * Note that generated name is only unique within this style generator, 
     * which is no problem, if only a single style generator takes care for a 
     * certain style family.
     * 
     * @param string $prefix 
     * @return string
     */
    protected function getUniqueStyleName( $prefix = 'style' )
    {
        if ( !isset( $this->prefixCounters[$prefix] ) )
        {
            $this->prefixCounters[$prefix] = 0;
        }
        return $prefix . ++$this->prefixCounters[$prefix];
    }
}

?>
