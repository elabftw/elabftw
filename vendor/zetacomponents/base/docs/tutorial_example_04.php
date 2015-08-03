<?php
require 'tutorial_autoload.php';

class myProgressFinder
{
    static public function findRecursiveCallback( ezcBaseFileFindContext $context, $sourceDir, $fileName, $fileInfo )
    {
        // ignore if we have a directory, but do print a "." and sleep for
        // extra demo time
        if ( $fileInfo['mode'] & 0x4000 )
        {
            echo ".";
            usleep( 100000 );
            return;
        }

        // update the statistics
        $context->elements[] = $sourceDir . DIRECTORY_SEPARATOR . $fileName;
        $context->count++;
        $context->size += $fileInfo['size'];
    }

    static public function findRecursive( $sourceDir, array $includeFilters = array(), array $excludeFilters = array() )
    {
        // create the context, and then start walking over the array
        $context = new ezcBaseFileFindContext;
        ezcBaseFile::walkRecursive( $sourceDir, $includeFilters, $excludeFilters,
                array( 'myProgressFinder', 'findRecursiveCallback' ), $context );

        // collect the statistics (which we don't do anything with in this example)
        $statistics['size'] = $context->size;
        $statistics['count'] = $context->count;

        // return the found and pattern-matched files
        sort( $context->elements );
        return $context->elements;
    }
}

$files = myProgressFinder::findRecursive( dirname( __FILE__ ) );
var_dump( $files );
?>
