/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

or_expression
  = _* expression:and_expression _* tail:or?
    {
      return new OrExpression($expression, $tail);
    }

or
  = orOp _* operand:and_expression _* tail:or?
    {
      return new OrOperand($operand, $tail);
    }

and_expression
  = expression:not _* tail:and?
    {
      return new AndExpression($expression, $tail);
    }

and
  = (andOp _*)? operand:not _* tail:and?
    {
      return new AndOperand($operand, $tail);
    }

not
  = notOp _* expression:(Parenthesis/List/Literal)
    {
      return new NotExpression($expression);
    }
  / e:(Parenthesis/List/Literal)
    {
      return $e;
    }

andOp '"AND", "&"'
  = $('AND'_+)
  / $('and'_+)
  / $('&')

orOp '"OR", "|"'
  = $('OR'_+)
  / $('or'_+)
  / $('|')

notOp '"NOT", "!"'
  = $('NOT'_+)
  / $('not'_+)
  / $('!')

Parenthesis 'expression in parenthesis'
  = '(' _* e:or_expression _* ')'
    {
      return $e;
    }

List 'quoted term'
  = word_list:(List1 / List2)
    {
      return new SimpleValueWrapper($word_list);
    }

List1
  = "'" word_list:List_String1 "'"
    {
      return $word_list;
    }

List_String1
  = chars:(
    [^\n\r\f\\']
    / '\\' nl
      {
        return '';
      }
    / escape
  )+
    {
      return join('', $chars);
    }

List2
  = '"' word_list:List_String2 '"'
    {
      return $word_list;
    }

List_String2
  = chars:(
    [^\n\r\f\\"]
    / '\\' nl
      {
        return '';
      }
    / escape
  )+
    {
      return join('', $chars);
    }

Literal 'term'
  = !orOp !andOp !notOp literal:$(String)+
    {
      return new SimpleValueWrapper($literal);
    }

String
  = chars:(
    [^\n\r\f\\"\\'() ]
    / '\\' nl
      {
        return '';
      }
    / escape
  )+
    {
      return join('', $chars);
    }

escape
  = unicode
  / '\\' ch:[^\r\n\f0-9a-f]i
    {
      return $ch;
    }

unicode
  = '\\u' digits:$(hex hex? hex? hex? hex? hex?)
    {
      return chr_unicode(intval($digits, 16));
    }

hex
  = [0-9a-f]i

nl
  = '\n'
  / '\r\n'
  / '\r'
  / '\f'

_ 'whitespace'
  = [\t ] //\n\r
