# Migrating from v2 to v3

Version 3 is the first significant departure in the zend-json API. In
particular, it features the removal of two features to new packages.

## zend-json-server

The `Zend\Json\Server` subcomponent was extracted to a new component,
[zend-json-server](https://zendframework.github.io/zend-json-server). Install it
using:

```bash
$ composer install zendframework/zend-json-server
```

All classes and functionality remain the same as in previous versions of
zend-json.

## XML to JSON support

v2 releases of zend-json provided `Zend\Json\Json::fromXml()`, which could be
used to convert an XML document to JSON. This functionality has been extracted
to a new component, [zend-xml2json](https://zendframework.github.io/zend-xml2json).
Install it using:

```bash
$ composer install zendframework/zend-xml2json
```

In order to use the functionality, you will need to modify your calls from
`Zend\Json\Json::fromXml()` to instead use `Zend\Xml2Json\Xml2Json::fromXml()`.
