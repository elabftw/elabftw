<?php
/**
 * File containing the ezcDocumentDocbookToOdtBaseHandler class.
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
 * Base class for ODT visitor handlers.
 *
 * ODT visitor handlers require a styler to be available, which is capable of
 * infering style information from DocBook elements and to apply them to ODT 
 * elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
abstract class ezcDocumentDocbookToOdtBaseHandler extends ezcDocumentElementVisitorHandler
{
    /**
     * ODT styler. 
     * 
     * @var ezcDocumentOdtStyler
     */
    protected $styler;

    /**
     * Creates a new handler which utilizes the given $styler. 
     * 
     * @param ezcDocumentOdtStyler $styler 
     */
    public function __construct( ezcDocumentOdtStyler $styler )
    {
        $this->styler = $styler;
    }
}

?>
