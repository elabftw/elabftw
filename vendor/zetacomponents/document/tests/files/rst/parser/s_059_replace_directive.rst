<?php

return ezcDocumentRstDocumentNode::__set_state(array(
   'depth' => 0,
   'line' => 0,
   'position' => 0,
   'type' => 1,
   'nodes' => 
  array (
    0 => 
    ezcDocumentRstSectionNode::__set_state(array(
       'title' => 
      ezcDocumentRstTitleNode::__set_state(array(
         'line' => 2,
         'position' => 1,
         'type' => 2,
         'nodes' => 
        array (
          0 => 
          ezcDocumentRstTextLineNode::__set_state(array(
             'line' => 1,
             'position' => 1,
             'type' => 4,
             'nodes' => 
            array (
            ),
             'token' => 
            ezcDocumentRstToken::__set_state(array(
               'type' => 5,
               'content' => 'Replace directive',
               'line' => 1,
               'position' => 1,
               'escaped' => false,
            )),
             'identifier' => NULL,
          )),
          1 => 
          ezcDocumentRstTextLineNode::__set_state(array(
             'line' => 1,
             'position' => 18,
             'type' => 4,
             'nodes' => 
            array (
            ),
             'token' => 
            ezcDocumentRstToken::__set_state(array(
               'type' => 2,
               'content' => '',
               'line' => 1,
               'position' => 18,
               'escaped' => false,
            )),
             'identifier' => NULL,
          )),
        ),
         'token' => 
        ezcDocumentRstToken::__set_state(array(
           'type' => 4,
           'content' => '=================',
           'line' => 2,
           'position' => 1,
           'escaped' => false,
        )),
         'identifier' => NULL,
      )),
       'depth' => 1,
       'reference' => NULL,
       'line' => 2,
       'position' => 1,
       'type' => 1,
       'nodes' => 
      array (
        0 => 
        ezcDocumentRstSubstitutionNode::__set_state(array(
           'name' => 
          array (
            0 => 
            ezcDocumentRstToken::__set_state(array(
               'type' => 5,
               'content' => 'reST',
               'line' => 4,
               'position' => 5,
               'escaped' => false,
            )),
          ),
           'line' => 4,
           'position' => 1,
           'type' => 51,
           'nodes' => 
          array (
            0 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 4,
               'position' => 1,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'reStructuredText',
                 'line' => 4,
                 'position' => 1,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
          ),
           'token' => 
          ezcDocumentRstToken::__set_state(array(
             'type' => 4,
             'content' => '..',
             'line' => 4,
             'position' => 1,
             'escaped' => false,
          )),
           'identifier' => NULL,
        )),
        1 => 
        ezcDocumentRstParagraphNode::__set_state(array(
           'indentation' => 0,
           'line' => 7,
           'position' => 15,
           'type' => 3,
           'nodes' => 
          array (
            0 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 6,
               'position' => 1,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'Yes, ',
                 'line' => 6,
                 'position' => 1,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            1 => 
            ezcDocumentRstMarkupSubstitutionNode::__set_state(array(
               'openTag' => false,
               'line' => 6,
               'position' => 11,
               'type' => 34,
               'nodes' => 
              array (
                0 => 
                ezcDocumentRstTextLineNode::__set_state(array(
                   'line' => 6,
                   'position' => 7,
                   'type' => 4,
                   'nodes' => 
                  array (
                  ),
                   'token' => 
                  ezcDocumentRstToken::__set_state(array(
                     'type' => 5,
                     'content' => 'reST',
                     'line' => 6,
                     'position' => 7,
                     'escaped' => false,
                  )),
                   'identifier' => NULL,
                )),
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '|',
                 'line' => 6,
                 'position' => 11,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            2 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 6,
               'position' => 12,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 1,
                 'content' => ' is a long word, so I can\'t blame anyone for wanting to abbreviate it.',
                 'line' => 6,
                 'position' => 12,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
          ),
           'token' => 
          ezcDocumentRstToken::__set_state(array(
             'type' => 2,
             'content' => '
',
             'line' => 7,
             'position' => 15,
             'escaped' => false,
          )),
           'identifier' => NULL,
        )),
        2 => 
        ezcDocumentRstParagraphNode::__set_state(array(
           'indentation' => 0,
           'line' => 11,
           'position' => 11,
           'type' => 3,
           'nodes' => 
          array (
            0 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 9,
               'position' => 1,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'As reStructuredText doesn\'t support nested inline markup, the only way to create a reference with styled text is to use substitutions with the "replace" directive:',
                 'line' => 9,
                 'position' => 1,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
          ),
           'token' => 
          ezcDocumentRstToken::__set_state(array(
             'type' => 2,
             'content' => '
',
             'line' => 11,
             'position' => 11,
             'escaped' => false,
          )),
           'identifier' => NULL,
        )),
        3 => 
        ezcDocumentRstParagraphNode::__set_state(array(
           'indentation' => 0,
           'line' => 13,
           'position' => 31,
           'type' => 3,
           'nodes' => 
          array (
            0 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 13,
               'position' => 1,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'I recommend you try ',
                 'line' => 13,
                 'position' => 1,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            1 => 
            ezcDocumentRstExternalReferenceNode::__set_state(array(
               'target' => false,
               'line' => 13,
               'position' => 29,
               'type' => 41,
               'nodes' => 
              array (
                0 => 
                ezcDocumentRstMarkupSubstitutionNode::__set_state(array(
                   'openTag' => false,
                   'line' => 13,
                   'position' => 28,
                   'type' => 34,
                   'nodes' => 
                  array (
                    0 => 
                    ezcDocumentRstTextLineNode::__set_state(array(
                       'line' => 13,
                       'position' => 22,
                       'type' => 4,
                       'nodes' => 
                      array (
                      ),
                       'token' => 
                      ezcDocumentRstToken::__set_state(array(
                         'type' => 5,
                         'content' => 'Python',
                         'line' => 13,
                         'position' => 22,
                         'escaped' => false,
                      )),
                       'identifier' => NULL,
                    )),
                  ),
                   'token' => 
                  ezcDocumentRstToken::__set_state(array(
                     'type' => 4,
                     'content' => '|',
                     'line' => 13,
                     'position' => 28,
                     'escaped' => false,
                  )),
                   'identifier' => NULL,
                )),
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '_',
                 'line' => 13,
                 'position' => 29,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            2 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 13,
               'position' => 30,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '.',
                 'line' => 13,
                 'position' => 30,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
          ),
           'token' => 
          ezcDocumentRstToken::__set_state(array(
             'type' => 2,
             'content' => '
',
             'line' => 13,
             'position' => 31,
             'escaped' => false,
          )),
           'identifier' => NULL,
        )),
        4 => 
        ezcDocumentRstSubstitutionNode::__set_state(array(
           'name' => 
          array (
            0 => 
            ezcDocumentRstToken::__set_state(array(
               'type' => 5,
               'content' => 'Python',
               'line' => 15,
               'position' => 5,
               'escaped' => false,
            )),
          ),
           'line' => 15,
           'position' => 1,
           'type' => 51,
           'nodes' => 
          array (
            0 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 15,
               'position' => 1,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'Python, ',
                 'line' => 15,
                 'position' => 1,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            1 => 
            ezcDocumentRstMarkupEmphasisNode::__set_state(array(
               'openTag' => false,
               'line' => 15,
               'position' => 13,
               'type' => 30,
               'nodes' => 
              array (
                0 => 
                ezcDocumentRstTextLineNode::__set_state(array(
                   'line' => 15,
                   'position' => 10,
                   'type' => 4,
                   'nodes' => 
                  array (
                  ),
                   'token' => 
                  ezcDocumentRstToken::__set_state(array(
                     'type' => 5,
                     'content' => 'the',
                     'line' => 15,
                     'position' => 10,
                     'escaped' => false,
                  )),
                   'identifier' => NULL,
                )),
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '*',
                 'line' => 15,
                 'position' => 13,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            2 => 
            ezcDocumentRstTextLineNode::__set_state(array(
               'line' => 15,
               'position' => 14,
               'type' => 4,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 1,
                 'content' => ' best language around',
                 'line' => 15,
                 'position' => 14,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
          ),
           'token' => 
          ezcDocumentRstToken::__set_state(array(
             'type' => 4,
             'content' => '..',
             'line' => 15,
             'position' => 1,
             'escaped' => false,
          )),
           'identifier' => NULL,
        )),
        5 => 
        ezcDocumentRstNamedReferenceNode::__set_state(array(
           'name' => 
          array (
            0 => 
            ezcDocumentRstToken::__set_state(array(
               'type' => 5,
               'content' => 'Python',
               'line' => 16,
               'position' => 5,
               'escaped' => false,
            )),
          ),
           'line' => 16,
           'position' => 1,
           'type' => 53,
           'nodes' => 
          array (
            0 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 13,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'http',
                 'line' => 16,
                 'position' => 13,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            1 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 17,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => ':',
                 'line' => 16,
                 'position' => 17,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            2 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 18,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '//',
                 'line' => 16,
                 'position' => 18,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            3 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 20,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'www',
                 'line' => 16,
                 'position' => 20,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            4 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 23,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '.',
                 'line' => 16,
                 'position' => 23,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            5 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 24,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'python',
                 'line' => 16,
                 'position' => 24,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            6 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 30,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 4,
                 'content' => '.',
                 'line' => 16,
                 'position' => 30,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            7 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 31,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 5,
                 'content' => 'org/',
                 'line' => 16,
                 'position' => 31,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
            8 => 
            ezcDocumentRstLiteralNode::__set_state(array(
               'line' => 16,
               'position' => 35,
               'type' => 50,
               'nodes' => 
              array (
              ),
               'token' => 
              ezcDocumentRstToken::__set_state(array(
                 'type' => 2,
                 'content' => '
',
                 'line' => 16,
                 'position' => 35,
                 'escaped' => false,
              )),
               'identifier' => NULL,
            )),
          ),
           'token' => 
          ezcDocumentRstToken::__set_state(array(
             'type' => 4,
             'content' => '..',
             'line' => 16,
             'position' => 1,
             'escaped' => false,
          )),
           'identifier' => NULL,
        )),
      ),
       'token' => 
      ezcDocumentRstToken::__set_state(array(
         'type' => 4,
         'content' => '=================',
         'line' => 2,
         'position' => 1,
         'escaped' => false,
      )),
       'identifier' => NULL,
    )),
  ),
   'token' => NULL,
   'identifier' => NULL,
));

