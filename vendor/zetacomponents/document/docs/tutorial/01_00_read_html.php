<?php

require 'tutorial_autoload.php';

$xhtml = new ezcDocumentXhtml();
$xhtml->loadFile( 'ez_components_introduction.html' );

$docbook = $xhtml->getAsDocbook();
echo $docbook->save();
?>
