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
  const fieldKeywords = /\b(?:author|body|category|elabid|group|owner|rating|state|status|title|visibility)\b/i;
  const quotedTerm = {
    alias: 'string',
    greedy: true,
    inside: {
      'important': /[\\]?(?:_|%)/,
    },
    pattern: /(["'])(?:\\['"]|(?!\1)[^\n\r\f])+\1/i,
  };
  const simpleTerm = {
    alias: 'string',
    greedy: true,
    inside: {
      'important': /[\\]?[_%]/,
    },
    pattern: /[^\n\r\f"'|!&(): ]+/,
  };
  const simpleOrQuotedTerm = '(?:' + quotedTerm['pattern'].source + '|' + simpleTerm['pattern'].source + ')';
  const strict = {
    greedy: true,
    inside: {
      'punctuation': /:/,
      'keyword': /s/,
    },
    pattern: /(?:s:)/,
  };
  const comparisonOperators = /(?:[<>]=?|!?=)/;

  Prism.languages.elabftwquery = {
    'field-bool': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'punctuation': /:/,
        'boolean': RegExp(bool, 'i'),
      },
      pattern: RegExp(
        '\\b(?:locked|timestamped)\\b'
          + ':'
          + bool,
        'i',
      ),
    },
    'field-date': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'operator': RegExp('\\.\\.|' + comparisonOperators.source),
        'punctuation': /[:.,/-]/,
        'number': /\d+/,
      },
      pattern: RegExp(
        '\\b(?:date|created_at|locked_at|timestamped_at)\\b'
          + ':'
          +'(?:'
            + date + '\\.\\.' + date
            + '|'
            + comparisonOperators.source + '?' + date
          + ')',
        'i',
      ),
    },
    'field-extrafield': {
      greedy: true,
      inside: {
        'keyword': /\b(?:extrafield)\b/i,
        // 'number': /\d+/,
        'strict': strict,
        'punctuation': /:/,
        'quoted-term': quotedTerm,
        'simple-term': simpleTerm,
      },
      pattern: RegExp(
        '\\bextrafield\\b'
        + ':'
        + strict.pattern.source + '?'
        + simpleOrQuotedTerm
        + ':'
        // uncouple the quotedTerm backreferences so that mixed quotations can be used i.e. 'key':"value" and "key":'value'
        + simpleOrQuotedTerm.replace(new RegExp('1', 'g'), '2'),
        'i',
      ),
    },
    'field-id': {
      alias: 'keyword',
      greedy: true,
      inside: {
        'punctuation': /:/,
        'number': /\d+/,
      },
      pattern: /\b(?:custom_id|id)\b:[1-9][0-9]*/i,
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
      greedy: true,
      inside: {
        'keyword': fieldKeywords,
        'strict': strict,
        'punctuation': /:/,
        'quoted-term': quotedTerm,
        'simple-term': simpleTerm,
      },
      pattern: RegExp(
        fieldKeywords.source
          + ':'
          + strict.pattern.source + '?'
          + simpleOrQuotedTerm,
        'i',
      ),
    },
    'operator': {
      lookbehind: true,
      pattern: /[&|!]|\b(?:and|or|not)\b/i,
    },
    'quoted-term': quotedTerm,
    'simple-term': simpleTerm,
    'punctuation': /[()]/,
  };
}(Prism));
