<?php

require 'tutorial_autoload.php';

$docbook = new ezcDocumentDocbook();
$docbook->loadFile( 'docbook.xml' );

$odt = new ezcDocumentOdt();
$odt->createFromDocbook( $docbook );

echo $odt->save();

?>
