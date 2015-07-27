<?php

require 'tutorial_autoload.php';

$document = new ezcDocumentEzXml();
$document->loadString( '<?xml version="1.0"?>
<section xmlns="http://ez.no/namespaces/ezpublish3">
    <header>Paragraph</header>
    <paragraph>Some content...</paragraph>
</section>' );

$docbook = $document->getAsDocbook();
echo $docbook->save();
?>
