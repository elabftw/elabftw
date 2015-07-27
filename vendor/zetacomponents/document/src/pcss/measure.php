<?php
/**
 * File containing the ezcDocumentPcssMeasure class
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
 * Pdf measure wrapper, including measure conversions
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentPcssMeasure
{
    /**
     * Internal value representation in millimeters
     *
     * @var float
     */
    protected $value;

    /**
     * One millimeter in inch
     */
    const MM_IN_INCH = 0.0393700787;

    /**
     * Resolution in DPI for transformations between mm and pixels.
     *
     * @var int
     */
    protected $resolution = 72;

    /**
     * Cache for conversions, to not reparse the same value again.
     *
     * @var array
     */
    protected static $cache = array();

    /**
     * Construct measure from input value
     *
     * @param mixed $value
     * @return void
     */
    public function __construct( $value )
    {
        $key = '_' . $value;
        if ( isset( self::$cache[$key] ) )
        {
            $this->value = self::$cache[$key];
            return;
        }

        if ( !preg_match( '(^\s*(?P<value>[+-]?\s*(?:\d*\.)?\d+)(?P<unit>[A-Za-z]+)?\s*$)S', $value, $match ) )
        {
            throw new ezcDocumentParserException( E_PARSE, "Could not parse '{$value}' as size value." );
        }

        $value = (float) $match['value'];
        $input = isset( $match['unit'] ) ? strtolower( $match['unit'] ) : 'mm';

        self::$cache[$key] = $this->value = $value / ( $f = $this->getUnitFactor( $input, $this->resolution ) );
    }

    /**
     * Static constructor wrapper
     *
     * Static constructor wrapper, because direct dereferencing does
     * not work with the new operator, and this makes the usage of
     * this simple wrpper class easier.
     *
     * @param mixed $value
     * @return ezcDocumentPcssMeasure
     */
    public static function create( $value )
    {
        return new ezcDocumentPcssMeasure( $value );
    }

    /**
     * Set resolution in dpi
     *
     * @param int $dpi
     * @return void
     */
    public function setResolution( $dpi )
    {
        $this->resolution = (int) $dpi;
    }

    /**
     * Get unit factor
     *
     * Get the factor for the given unit, so values can be transformed from the
     * passed unit into milli meters.
     *
     * @param string $unit
     * @param int $resolution
     * @return void
     */
    protected function getUnitFactor( $unit, $resolution )
    {
        switch ( $unit )
        {
            case 'mm':
                return 1;
            case 'in':
                return self::MM_IN_INCH;
            case 'px':
                // The pixel transformation depends on the current resolution
                return self::MM_IN_INCH * $resolution;
            case 'pt':
                // Points are defined as 72 points per inch
                return self::MM_IN_INCH * 72;
            default:
                throw new ezcDocumentParserException( E_PARSE, "Unknown unit '$unit'." );
        }
    }

    /**
     * Convert values
     *
     * Convert measure values from the PCSS input file into another unit. The
     * input unit is read from the passed value and defaults to milli meters.
     * The output unit can be specified as the second parameter and also
     * default to milli meters.
     *
     * Supported units currently are: mm, px, pt, in
     *
     * Optionally a resolution (dpi) can specified for the
     * conversion of pixel values.
     *
     * @param string $format
     * @param int $resolution
     * @return float
     */
    public function get( $format = 'mm', $resolution = null )
    {
        $resolution = $resolution === null ? $this->resolution : $resolution;
        return $this->value * $this->getUnitFactor( $format, $resolution );
    }
}

?>
