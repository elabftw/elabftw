<?php

require 'tutorial_autoload.php';

$odt = new ezcDocumentOdt();
$odt->loadFile( 'simple.fodt' );

$docbook = $odt->getAsDocbook();
echo $docbook->save();

?>
