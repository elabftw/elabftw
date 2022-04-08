/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

OrExpression
  = _* expression:AndExpression tail:(_+ tail:Or {return $tail;})?
  {
    return new OrExpression($expression, $tail);
  }

Or
  = OrOp operand:AndExpression tail:(_+ tail:Or {return $tail;})?
  {
    return new OrOperand($operand, $tail);
  }

OrOp '"OR", "|"'
  = $('OR'i_+)
  / $('|'_*)

AndExpression
  = expression:Not tail:(_+ tail:And {return $tail;})?
  {
    return new AndExpression($expression, $tail);
  }

And
  = AndOp? operand:Not tail:(_+ tail:And {return $tail;})?
  {
    return new AndOperand($operand, $tail);
  }

AndOp '"AND", "&"'
  = $('AND'i_+)
  / $('&'_*)

Not
  = NotOp expression:Wrapper
  {
    return new NotExpression($expression);
  }
  / e:Wrapper
  {
    return $e;
  }

NotOp '"NOT", "-", "!"'
  = $('NOT'i_+)
  / $('-'_*)
  / $('!'_*)

Wrapper
  = Parenthesis
  / Fields
  / List
  / Literal

Parenthesis 'expression in parenthesis'
  = '(' e:OrExpression _* ')'
    {
      return $e;
    }

Fields
  = Field
  / FieldDate
  / FieldBoolean
  / FieldRating

Field
  = field:('author'i / 'body'i / 'category'i / 'elabid'i / 'group'i / 'status'i / 'title'i / 'visibility'i) ':' term:(List / Literal)
  {
    return new Field($field, $term);
  }

FieldDate
  = 'date'i ':' date:(DateBetween / DateSimple)
  {
    return new DateField($date);
  }

DateBetween
  = dateFrom:Date '..' dateTo:Date
  {
    return array(
      'type' => 'range',
      'date' => $dateFrom,
      'dateTo' => $dateTo,
    );
  }

DateSimple
  = operator:$('<=' / '<' / '>=' / '>' / '=' / '!=')? date:Date
  {
    return array(
      'type' => 'simple',
      'operator' => $operator,
      'date' => $date,
    );
  }

Date
  = year:YYYY DateSeparator? month:MM DateSeparator? day:DD
  {
    return $year . $month . $day;
  }

DateSeparator
  = '-'
  / '/'
  / '.'
  / ','

YYYY
  = year:$(Digit Digit Digit Digit)
  {
    return $year;
  }

MM
  = month:$('0' Digit19 / '1' [0-2] )
  {
    return $month;
  }

DD
  = day:$('0' Digit19 / [1-2] Digit / '3' [01])
  {
    return $day;
  }

FieldBoolean
  = field:('locked'i / 'timestamped'i / 'attachment'i) ':' term:Boolean
  {
    return new Field($field, new SimpleValueWrapper($term));
  }

// return strings because SimpleValueWrapper() takes strings
Boolean
  = ('0' / 'false' / 'no' / 'off')
  {
    return '0';
  }
  / ('1' / 'true' / 'yes' / 'on' / '')
  {
    return '1';
  }

// return strings because SimpleValueWrapper() takes strings
FieldRating
  = 'rating'i ':' term:($([0-5]) / 'unrated'i { return '0';})
  {
    return new Field('rating', new SimpleValueWrapper($term));
  }

List 'quoted term'
  = wordList:(List1 / List2)
  {
    return new SimpleValueWrapper($wordList);
  }

List1
  = "'" wordList:ListString1 "'"
  {
    return $wordList;
  }

ListString1
  = chars:(
    [^\n\r\f\\']
    / nlEscaped
    / Escape
  )+
  {
    return join("", $chars);
  }

List2
  = '"' wordList:ListString2 '"'
  {
    return $wordList;
  }

ListString2
  = chars:(
    [^\n\r\f\\"]
    / nlEscaped
    / Escape
  )+
  {
    return join("", $chars);
  }

Literal 'term'
  // Need to negate operators here to prevent them being a term themselves
  = !OrOp !AndOp !NotOp literal:$(String)
  {
    return new SimpleValueWrapper($literal);
  }

String
  = chars:(
    [^\n\r\f\\"\\'() ]
    / nlEscaped
    / Escape
  )+
  {
    return join("", $chars);
  }

Escape
  = Unicode
  / '\\' ch:[^\r\n\f0-9a-f]i
  {
    return $ch;
  }

Unicode
  = '\\u' digits:$(Hex Hex? Hex? Hex? Hex? Hex?)
  {
    return chr_unicode(intval($digits, 16));
  }

Hex
  = [0-9a-f]i

Digit
  = [0-9]

Digit19
  = [1-9]

nlEscaped
  =  '\\' $('\r\n' / '\r' / '\n' / '\f')
  {
    return "";
  }

_ 'whitespace'
  = [\t\n\r ]
