<?php
/**
 * File containing the ezcDocumentOdtStyleTableRowPropertyGenerator class.
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
 * Table row property generator.
 *
 * Creates and fills the <style:table-row-properties/> element.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentOdtStyleTableRowPropertyGenerator extends ezcDocumentOdtStylePropertyGenerator
{
    /**
     * Creates a new table-row-properties generator.
     * 
     * @param ezcDocumentOdtPcssConverterManager $styleConverters 
     */
    public function __construct( ezcDocumentOdtPcssConverterManager $styleConverters )
    {
        parent::__construct(
            $styleConverters,
            array(
                'background-color',
            )
        );
    }

    /**
     * Creates the table-row-properties element.
     *
     * Creates the table-row-properties element in $parent and applies the fitting $styles.
     * 
     * @param DOMElement $parent 
     * @param array $styles 
     * @return DOMElement The created property
     */
    public function createProperty( DOMElement $parent, array $styles )
    {
        $prop = $parent->appendChild(
            $parent->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_STYLE,
                'style:table-row-properties'
            )
        );

        $this->applyStyleAttributes(
            $prop,
            $styles
        );

        return $prop;
    }
}

?>
