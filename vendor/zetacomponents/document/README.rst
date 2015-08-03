==================================
Zeta Components Document component
==================================

.. image:: https://travis-ci.org/zetacomponents/Document.png?branch=master
   :target: https://travis-ci.org/zetacomponents/Document

The document component offers transformations between different semantic markup
languages, like:

- `ReStructured text`__
- `XHTML`__
- `Docbook`__
- `eZ Publish XML markup`__
- Wiki markup languages, like: Creole__, Dokuwiki__ and Confluence__
- `Open Document Text`__ as used by `OpenOffice.org`__ and other office suites

Each format supports conversions from and to docbook as a central intermediate
format and may implement additional shortcuts for conversions from and to other
formats. Not each format can express the same semantics, so there may be some
information lost.

__ http://docutils.sourceforge.net/rst.html
__ http://www.w3.org/TR/xhtml1/
__ http://www.docbook.org/
__ http://doc.ez.no/eZ-Publish/Technical-manual/4.x/Reference/XML-tags
__ http://www.wikicreole.org/
__ http://www.dokuwiki.org/dokuwiki
__ http://confluence.atlassian.com/renderer/notationhelp.action?section=all
__ http://www.oasis-open.org/committees/tc_home.php?wg_abbrev=office
__ http://www.openoffice.org/

To check out features and usage of the graph component check out the
tutorial__.

__ docs/tutorial.txt


..
   Local Variables:
   mode: rst
   fill-column: 79
   End: 
   vim: et syn=rst tw=79
