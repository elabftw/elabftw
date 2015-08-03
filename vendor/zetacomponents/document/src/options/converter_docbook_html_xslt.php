<?php
/**
 * File containing the ezcDocumentDocbookToHtmlXsltConverterOptions class.
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
 */

/**
 * Class containing the basic options for the docbook to HTMl conversion.
 *
 * By default the XSLT published by the OASIS [1] is used, with the options
 * documented here:
 * http://docbook.sourceforge.net/release/xsl/current/doc/html/
 *
 * [1] http://docbook.sourceforge.net/release/xsl/current/html/docbook.xsl
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentDocbookToHtmlXsltConverterOptions extends ezcDocumentXsltConverterOptions
{
    /**
     * Constructs an object with the specified values.
     *
     * @throws ezcBasePropertyNotFoundException
     *         if $options contains a property not defined
     * @throws ezcBaseValueException
     *         if $options contains a property with a value not allowed
     * @param array(string=>mixed) $options
     */
    public function __construct( array $options = array() )
    {
        $this->xslt       = 'http://docbook.sourceforge.net/release/xsl/current/html/docbook.xsl';
        $this->parameters = array(
            '' => array(
                'make.valid.html' => '1',
            ),
        );

        parent::__construct( $options );
    }
}

?>
