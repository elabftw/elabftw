<?php

require 'tutorial_autoload.php';

$docbook = new ezcDocumentDocbook();
$docbook->loadFile( 'docbook.xml' );

$document = new ezcDocumentWiki();
$document->createFromDocbook( $docbook );
echo $document->save();
?>
