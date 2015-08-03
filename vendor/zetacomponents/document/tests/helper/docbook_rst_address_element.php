<?php

class myAddressElementHandler extends ezcDocumentDocbookToRstBaseHandler
{
    public function handle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $root .= $this->renderDirective( 'address', $node->textContent, array() );
        return $root;
    }
}

?>
