<?php

require 'tutorial_autoload.php';

$document = new ezcDocumentRst();
$document->loadFile( '../tutorial.txt' );

$docbook = $document->getAsDocbook();
echo $docbook->save();
?>
