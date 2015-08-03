<?php

require 'tutorial_autoload.php';

// Load custom directive
require '00_01_address_directive.php';

$document = new ezcDocumentRst();
$document->registerDirective( 'address', 'myAddressDirective' );
$document->loadString( <<<EORST
Address example
===============

.. address:: John Doe
    :street: Some Lane 42
EORST
);

$docbook = $document->getAsDocbook();
echo $docbook->save();
?>
