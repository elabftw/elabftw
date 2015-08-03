<?php

require 'tutorial_autoload.php';

$docbook = new ezcDocumentDocbook();
$docbook->loadFile( 'docbook.xml' );

$rst = new ezcDocumentRst();
$rst->createFromDocbook( $docbook );

echo $rst->save();

?>
