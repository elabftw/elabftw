<?php
/**
 * File containing the ezcDocumentPcssLayoutDirective class.
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
 * Pdf CSS layout directive.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPcssLayoutDirective extends ezcDocumentPcssDirective
{
    /**
     * Regular expression compiled from directive address.
     *
     * @var string
     */
    protected $regularExpression = null;

    /**
     * Compile regular expression.
     *
     * Compiles the address of this style directive into a PCRE regular
     * expression, which then can be matched against location IDs.
     */
    protected function compileRegularExpression()
    {
        $regexp  = '(^';

        $address = $this->address;
        while ( $token = array_shift( $address ) )
        {
            // Check for direct descendants
            if ( strpos( $token, '>' ) === 0 )
            {
                $token   = preg_replace( '(>[\\t\\x20]+)', '', $token );
                $regexp .= '/' . preg_quote( $token );
            }
            elseif ( ( strpos( $token, '.' ) === 0 ) ||
                     ( strpos( $token, '#' ) === 0 ) )
            {
                $regexp .= '(?:/[^/]+)*/[^.]+';
                array_unshift( $address, $token );
            }
            else
            {
                $regexp .= '(?:/[^/]+)*/' . preg_quote( $token );
            }

            // Append optional class and ID restrictions
            $restrictions = array();
            while ( isset( $address[0] ) &&
                    ( ( strpos( $address[0], '.' ) === 0 ) ||
                      ( strpos( $address[0], '#' ) === 0 ) ) )
            {
                $token = array_shift( $address );
                $restrictions[$token[0]] = substr( $token, 1 );
            }

            // Append optional restrictions
            if ( isset( $restrictions['.'] ) )
            {
                $regexp .= '\\.(?:[a-z0-9_-]+_)?' . preg_quote( $restrictions['.'] ) . '(?:_[a-z0-9_-]+)?';
            }
            else
            {
                $regexp .= '(?:\\.[a-z0-9_-]+)?';
            }

            // Append optional restrictions
            if ( isset( $restrictions['#'] ) )
            {
                $regexp .= '#' . preg_quote( $restrictions['#'] );
            }
            else
            {
                $regexp .= '(?:#[a-z0-9_-]+)?';
            }

            $regexp .= '(?:\\[[^]]+\\])*';
        }

        $regexp .= '$)S';
        $this->regularExpression = $regexp;
    }

    /**
     * Return a PCRE regular expression for directive address.
     *
     * Return a PCRE regular expression representing the address of
     * the directive, intended to match location IDs representing
     * the docbook element nodes.
     *
     * @param string $locationId
     * @return string
     */
    public function getRegularExpression()
    {
        if ( $this->regularExpression === null )
        {
            $this->compileRegularExpression();
        }

        return $this->regularExpression;
    }

    /**
     * Recreate directive from var_export.
     *
     * @param array $properties
     * @return ezcDocumentPcssDirective
     */
    public static function __set_state( $properties )
    {
        return new ezcDocumentPcssLayoutDirective(
            $properties['address'],
            $properties['formats'],
            $properties['file'],
            $properties['line'],
            $properties['position']
        );
    }
}
?>
