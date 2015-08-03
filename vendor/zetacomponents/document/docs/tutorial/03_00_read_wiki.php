<?php

require 'tutorial_autoload.php';

$document = new ezcDocumentWiki();
$document->loadString( '
= Example text =

Just some exaple paragraph with a heading, some **emphasis** markup and a
[[http://ezcomponents.org|link]].' );

$docbook = $document->getAsDocbook();
echo $docbook->save();
?>
