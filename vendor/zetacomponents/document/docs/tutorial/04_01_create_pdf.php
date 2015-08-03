<?php

require 'tutorial_autoload.php';

// Convert some input RSTfile to docbook
$document = new ezcDocumentRst();
$document->loadFile( './article/introduction.txt' );

$pdf = new ezcDocumentPdf();
$pdf->options->errorReporting = E_PARSE | E_ERROR | E_WARNING;
$pdf->createFromDocbook( $document->getAsDocbook() );

file_put_contents( __FILE__ . '.pdf', $pdf );

?>
