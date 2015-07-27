<?php

require 'tutorial_autoload.php';

$document = new ezcDocumentWiki();
$document->options->tokenizer = new ezcDocumentWikiConfluenceTokenizer();
$document->loadString( '
h1. Example text

Just some exaple paragraph with a heading, some *emphasis* markup and a
[link|http://ezcomponents.org].' );

$docbook = $document->getAsDocbook();
echo $docbook->save();
?>
