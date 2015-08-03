<?php
/**
 * File containing the abstract ezcDocumentEzXmlLinkProvider base class.
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
 * Class providing access to links referenced in eZXml documents by url IDs,
 * node IDs or object IDs.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentEzXmlLinkProvider
{
    /**
     * Fetch URL by ID
     *
     * Fetch and return URL referenced by url_id property.
     *
     * @param string $id
     * @param string $view
     * @param string $show_path
     * @return string
     */
    abstract public function fetchUrlById( $id, $view, $show_path );

    /**
     * Fetch URL by node ID
     *
     * Create and return the URL for a referenced node.
     *
     * @param string $id
     * @param string $view
     * @param string $show_path
     * @return string
     */
    abstract public function fetchUrlByNodeId( $id, $view, $show_path );

    /**
     * Fetch URL by ID
     *
     * Create and return the URL for a referenced object.
     *
     * @param string $id
     * @param string $view
     * @param string $show_path
     * @return string
     */
    abstract public function fetchUrlByObjectId( $id, $view, $show_path );
}

?>
