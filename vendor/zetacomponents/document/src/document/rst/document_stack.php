<?php
/**
 * File containing the ezcDocumentRstDirective class
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
 * RST document stack
 *
 * @package Document
 * @version //autogen//
 */
class ezcDocumentRstStack implements ArrayAccess, Countable
{
    /**
     * Data container for the document stack implementation
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Number of elements on the stack
     *
     * We are caching this value for faster access performance, and because we 
     * are only using a very limited internal methods which actually modify the 
     * satck.
     * 
     * @var int
     */
    protected $count = 0;

    /**
     * Construct stack from array
     * 
     * @param array $array 
     * @return void
     */
    public function __construct( array $array = array() )
    {
        $this->data  = array_reverse( $array );
        $this->count = count( $this->data );
    }

    /**
     * Prepend element to the document stack
     * 
     * @param mixed $element 
     * @return void
     */
    public function unshift( $element )
    {
        $this->data[] = $element;
        return ++$this->count;
    }

    /**
     * Prepend element to the document stack
     * 
     * @param mixed $element 
     * @return void
     */
    public function push( $element )
    {
        array_unshift( $this->data, $element );
        return ++$this->count;
    }

    /**
     * Get element from the beginning of the stack
     * 
     * @return mixed
     */
    public function shift()
    {
        if ( ( $element = array_pop( $this->data ) ) !== null )
        {
            --$this->count;
        }
        return $element;
    }

    /**
     * Prepend another array to the stack
     *
     * Prepends an array with tokens to the current stack. Equivalent to 
     * calling $array = array_merge( $data, $array ); with common array 
     * functions.
     * 
     * @param array $data 
     * @return void
     */
    public function prepend( array $data )
    {
        foreach ( array_reverse( $data ) as $element )
        {
            $this->data[] = $element;
        }
        $this->count += count( $data );
    }

    /**
     * Get stack contents as plain PHP array
     * 
     * @return array
     */
    public function asArray( $limit = null )
    {
        $data = $limit === null ? $this->data : array_slice( $this->data, -$limit );
        return array_reverse( $data );
    }

    /**
     * Returns if the given offset exists.
     *
     * This method is part of the ArrayAccess interface to allow access to the
     * data of this object as if it was an array.
     * 
     * @param string $key
     * @return bool
     */
    public function offsetExists( $key )
    {
        return isset( $this->data[$key] );
    }

    /**
     * Returns the element with the given offset. 
     *
     * This method is part of the ArrayAccess interface to allow access to the
     * data of this object as if it was an array. 
     * 
     * @param string $key
     * @return mixed
     *
     * @throws ezcBasePropertyNotFoundException
     *         If no dataset with identifier exists
     */
    public function offsetGet( $key )
    {
        $key = $this->count - $key - 1;
        if ( !isset( $this->data[$key] ) )
        {
            throw new ezcBasePropertyNotFoundException( $key );
        }

        return $this->data[$key];
    }

    /**
     * Set the element with the given offset. 
     *
     * This method is part of the ArrayAccess interface to allow access to the
     * data of this object as if it was an array. 
     *
     * Setting of not yet existing offsets in the stack is not allowed and will 
     * return a ezcBaseValueException.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     *
     * @throws ezcBaseValueException
     *         Setting unknown offsets is not allowed
     */
    public function offsetSet( $key, $value )
    {
        $key = $this->count - $key - 1;
        if ( !isset( $this->data[$key] ) )
        {
            throw new ezcBasePropertyNotFoundException( $key );
        }

        $this->data[$key] = $value;
    }

    /**
     * Unset the element with the given offset. 
     *
     * This method is part of the ArrayAccess interface to allow access to the
     *
     * Is not permitted for this stack implementation.
     * 
     * @param string $key
     * @return void
     *
     * @throws ezcBaseValueException
     *         Setting values is not allowed
     */
    public function offsetUnset( $key )
    {
        throw new ezcBaseValueException( $key, $value, 'none' );
    }

    /**
     * Selects the very first dataset and returns it.
     * This method is part of the Iterator interface to allow access to the 
     * datasets of this row by iterating over it like an array (e.g. using
     * foreach).
     *
     * @return mixed
     */
    public function rewind()
    {
        return end( $this->data );
    }

    /**
     * Returns the number of datasets in the row.
     *
     * This method is part of the Countable interface to allow the usage of
     * PHP's count() function to check how many datasets exist.
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }
}

?>
