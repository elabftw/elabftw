<?php

class ezcDocumentTestDummyDirective extends ezcDocumentRstDirective
{
    public function toDocbook( DOMDocument $document, DOMElement $root )
    {
        // Just do nothing
    }
}

class ezcDocumentTestDummyRole extends ezcDocumentRstTextRole
{
    public function toDocbook( DOMDocument $document, DOMElement $root )
    {
        // Just do nothing
    }
}

class ezcDocumentTestDummyXhtmlDirective extends ezcDocumentRstDirective implements ezcDocumentRstXhtmlDirective
{
    public function toDocbook( DOMDocument $document, DOMElement $root )
    { /* Just do nothing */ }

    public function toXhtml( DOMDocument $document, DOMElement $root )
    { /* Just do nothing */ }
}

?>
