<?php
/**
 * File containing the abstract ezcDocumentDocbookToRstBaseHandler base class.
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
 * Basic converter which stores a list of handlers for each node in the docbook
 * element tree. Those handlers will be executed for the elements, when found.
 * The handler can then handle the repective subtree.
 *
 * Additional handlers may be added by the user to the converter class.
 *
 * @package Document
 * @version //autogen//
 */
abstract class ezcDocumentDocbookToRstBaseHandler extends ezcDocumentElementVisitorHandler
{
    /**
     * Render a directive
     *
     * Render a directive with the given paramters.
     *
     * @param string $name
     * @param string $parameter
     * @param array $options
     * @param string $content
     * @return string
     */
    protected function renderDirective( $name, $parameter, array $options, $content = null )
    {
        $indentation = str_repeat( ' ', ezcDocumentDocbookToRstConverter::$indentation );

        // Show directive with given parameters
        $directive = sprintf( "\n%s.. %s:: %s\n",
            $indentation,
            $name,
            $parameter
        );

        // Append options
        foreach ( $options as $key => $value )
        {
            $directive .= sprintf( "%s   :%s: %s\n",
                $indentation,
                ezcDocumentDocbookToRstConverter::escapeRstText( $key ),
                ezcDocumentDocbookToRstConverter::escapeRstText( $value )
            );
        }

        // Append content, if given
        if ( $content !== null )
        {
            $directive .= "\n" . str_repeat( ' ', ezcDocumentDocbookToRstConverter::$indentation + 3 ) .
                trim( ezcDocumentDocbookToRstConverter::wordWrap( $content, 3 ) ) . "\n";
        }

        // Append an additional newline after the directive contents
        $directive .= "\n";

        return $directive;
    }
}

?>
