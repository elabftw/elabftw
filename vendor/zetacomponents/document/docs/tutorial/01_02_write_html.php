<?php

require 'tutorial_autoload.php';

$docbook = new ezcDocumentDocbook();
$docbook->loadFile( 'docbook.xml' );

$html = new ezcDocumentXhtml();
$html->createFromDocbook( $docbook );

echo $html->save();

?>
