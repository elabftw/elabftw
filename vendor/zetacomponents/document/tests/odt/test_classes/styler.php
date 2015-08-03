<?php

class ezcDocumentOdtTestStyler implements ezcDocumentOdtStyler
{
    public $odtDocument;

    public $seenElements = array();

    public function init( DOMDocument $odtDocument )
    {
        $this->odtDocument = $odtDocument;
    }

    public function applyStyles( ezcDocumentLocateable $docBookElement, DOMElement $odtElement )
    {
        $this->seenElements[] = array(
            $docBookElement->tagName,
            $odtElement->tagName
        );
    }
}

?>
