<?php

require 'tutorial_autoload.php';

class myLinkProvider extends ezcDocumentEzXmlLinkProvider
{
    public function fetchUrlById( $id, $view, $show_path )
    {
        return 'http://host/path/' . $id;
    }

    public function fetchUrlByNodeId( $id, $view, $show_path ) {}
    public function fetchUrlByObjectId( $id, $view, $show_path ) {}
}

$document = new ezcDocumentEzXml();
$document->loadString( '<?xml version="1.0"?>
<section xmlns="http://ez.no/namespaces/ezpublish3">
    <header>Paragraph</header>
    <paragraph>Some content, with a <link url_id="1">link</link>.</paragraph>
</section>' );

// Set link provider
$converter = new ezcDocumentEzXmlToDocbookConverter();
$converter->options->linkProvider = new myLinkProvider();

$docbook = $converter->convert( $document );
echo $docbook->save();
?>
