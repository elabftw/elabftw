/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Prism from 'prismjs';

(function(Prism) {
  const date = '\\d{4}[.,/-]?(?:0[1-9]|1[012])[.,/-]?(?:0[1-9]|[12]\\d|3[01])';
  const bool = '\\b(?:false|true|yes|no|on|off|1|0)\\b';
  const fieldKeywords = /\b(?:attachment|author|body|category|elabid|group|rating|status|title|visibility)\b/i;
  const quotedTerm = {
    alias: 'string',
    greedy: true,
    inside: {
      'important': /[\\]?(?:_|%)/,
    },
    pattern: /(["'])(?:(?!\1)[^\n\r\f])+\1/i,
  };
  const simpleTerm = {
    alias: 'string',
    greedy: true,
    inside: {
      'important': /[\\]?(?:_|%)/,
    },
    pattern: /[^\n\r\f"'|!&() -]+/,
  };

  Prism.languages.elabftwquery = {
    'field-bool': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'punctuation': /:/,
        'boolean': RegExp(bool, 'i'),
      },
      pattern: RegExp('\\b(?:attachment|locked|timestamped)\\b:' + bool, 'i'),
    },
    'field-date': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'operator': /\.\.|[<>]=?|!?=/,
        'punctuation': /[:.,/-]/,
        'number': /\d+/,
      },
      pattern: RegExp('\\bdate\\b:(?:' + date + '\\.\\.' + date + '|(?:[<>]=?|!?=)?' + date + ')', 'i'),
    },
    'field-rating': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'punctuation': /:/,
        'constant': /\bunrated\b/i,
        'number': /[0-5]/,
      },
      pattern: /\brating\b:(?:[0-5]|\bunrated\b)/i,
    },
    'field': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'keyword': fieldKeywords,
        'strict': {
          pattern: /:s:/,
          inside: {
            'punctuation': /:/,
            'keyword': /s/,
          },
        },
        'punctuation': /:/,
        'quoted-term': quotedTerm,
        'simple-term': simpleTerm,
      },
      pattern: RegExp(fieldKeywords.source + ':(?:s:)?(?:' + quotedTerm['pattern'].source + '|' + simpleTerm['pattern'].source + ')', 'i'),
    },
    'operator': {
      lookbehind: true,
      pattern: /[&|!-]|\b(?:and|or|not)\b/i,
    },
    'quoted-term': quotedTerm,
    'simple-term': simpleTerm,
    'punctuation': /[()]/,
  };
}(Prism));
