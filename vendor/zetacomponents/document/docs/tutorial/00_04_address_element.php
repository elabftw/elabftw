<?php

require 'tutorial_autoload.php';

$docbook = new ezcDocumentDocbook();
$docbook->loadFile( 'address.xml' );

class myAddressElementHandler extends ezcDocumentDocbookToRstBaseHandler
{
    public function handle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $root .= $this->renderDirective( 'address', $node->textContent, array() );
        return $root;
    }
}

$converter = new ezcDocumentDocbookToRstConverter();
$converter->setElementHandler( 'docbook', 'address', new myAddressElementHandler() );

$rst = $converter->convert( $docbook );
echo $rst->save();

?>
