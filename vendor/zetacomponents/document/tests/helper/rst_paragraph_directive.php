<?php

class ezcDocumentTestParagraphDirective extends ezcDocumentRstDirective
{
    public function toDocbook( DOMDocument $document, DOMElement $root )
    {
        $article = $this->parseTokens(
            $this->node->tokens,
            new ezcDocumentRstDocbookVisitor( new ezcDocumentRst(), $this->path )
        )->documentElement;

        for ( $i = 0; $i < $article->childNodes->length; ++$i )
        {
            $child = $article->childNodes->item( $i );
            if ( isset( $this->node->options['class'] ) &&
                 ( $child->nodeType === XML_ELEMENT_NODE ) )
            {
                $child->setAttribute( 'Role', trim( $this->node->options['class'] ) );
            }

            $root->appendChild( $document->importNode( $child, true ) );
        }
    }
}

?>
