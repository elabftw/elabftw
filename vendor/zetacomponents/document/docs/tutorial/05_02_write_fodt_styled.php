<?php

require 'tutorial_autoload.php';

$docbook = new ezcDocumentDocbook();
$docbook->loadFile( 'docbook.xml' );

$converter = new ezcDocumentDocbookToOdtConverter();

$converter->options->styler->addStylesheetFile( 'custom.css' );

$odt = $converter->convert( $docbook );

echo $odt->save();

?>
