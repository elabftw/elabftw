# jQuery PageSlide

PageSlide is a jQuery plugin which slides a webpage over to reveal an additional interaction pane.

**Note: This respository is not actively maintained. In fact, I don't recommend using it. There are plenty of [alternatives out there](http://www.unheap.com/section/navigation/drawer-responsive/).**

## Demo

There are a couple of examples included with this package. Or, if you can't wait to download it, see it live on the [responsive demo](http://srobbin.github.com/jquery-pageslide) or [original project page](http://srobbin.com/jquery-plugins/pageslide/).

## Options

### speed

The speed at which the page slides over. Accepts standard jQuery effects speeds (e.g. 'fast', 'normal' or milliseconds). (default=200)

### direction

Which direction does the page slide? Accepts 'left' or 'right'. (default='right')

### modal

By default, when pageslide opens, you can click on the document to close it. If modal is set to 'true', then you must explicitly close PageSlide using $.pageslide.close(); (default=false)

### iframe

By default, linked pages are loaded into an iframe. Set this to false if you don't want an iframe. (default=true)

### href

Override the source of the content. Optional in most cases, but required when opening pageslide programmatically (e.g. <code>$.pageslide({ href: '#some-element' });</code> ) (default=null)

## Setup

In the HEAD tag:
```
<link rel="stylesheet" type="text/css" href="jquery.pageslide.css">
```

Ideally, near the bottom of the page.
```
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript" src="jquery.pageslide.min.js"></script>
```

To use, call pageslide on an <code><a></code> tag that either links to a page or an anchor of a hidden element.
```
<script type="text/javascript">
    $('a').pageslide();
</script>
```

Or, open pageslide programatically:
```
<script type="text/javascript">
    $.pageslide({ href: '#some-element' });
    $.pageslide({ href: 'some-page.html' });
</script>
```

To close pageslide programatically:
```
<script type="text/javascript">
    $.pageslide.close();
</script>
```

## Changelog

### Version 2.0

* Completely rewritten
* Externalized CSS
* Content loaded into an iframe

### Version 1.3

* Older versions of PageSlide are located in this repository, however if you would like to contribute to the original plugin's development, please use contributor "Derek Perez's repository":https://github.com/perezd/jquery-pageslide.
