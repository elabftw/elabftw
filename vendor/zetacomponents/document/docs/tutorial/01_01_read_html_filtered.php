<?php

require 'tutorial_autoload.php';

$xhtml = new ezcDocumentXhtml();
$xhtml->setFilters( array(
    new ezcDocumentXhtmlElementFilter(),
    new ezcDocumentXhtmlMetadataFilter(),
    new ezcDocumentXhtmlXpathFilter( '//div[@class="document"]' ),
) );
$xhtml->loadFile( 'ez_components_introduction.html' );

$docbook = $xhtml->getAsDocbook();
echo $docbook->save();
?>
