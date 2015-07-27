#!/usr/bin/env php
<?php

$lines     = file( dirname( __FILE__ ) . '/parserTests.txt' );
$lineCount = count( $lines );
            
$test      = null;
$testNr    = 0;

for ( $nr = 0; $nr < $lineCount; ++$nr )
{
    switch ( trim( $lines[$nr] ) )
    {
        case '!! test':
            $test = sprintf( 's_%03d_%s.txt',
                ++$testNr,
                preg_replace( '([^0-9a-zA-Z]+)', '_', trim( $lines[$nr + 1] ) )
            );
            break;

        case '!! input':
            if ( $test !== null )
            {
                $fp = fopen( $test, 'w' );
                ++$nr;
                while ( ( trim( $lines[$nr] ) !== '!! result' ) &&
                        ( trim( $lines[$nr] ) !== '!! end' ) )
                {
                    fwrite( $fp, $lines[$nr++] );
                }
                fclose( $fp );
            }
            $test = null;
            break;
    }
}

?>
