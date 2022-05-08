/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

OrExpression
  = _* expression:AndExpression tail:OrOperand?
  {
    return new OrExpression($expression, $tail);
  }

OrOperand
  = OrOperator operand:AndExpression tail:OrOperand?
  {
    return new OrOperand($operand, $tail);
  }

OrOperator '"OR", "|"'
  = (_+ 'OR'i _+)
  / (_* '|' _*)

AndExpression
  = expression:(NotExpression / Wrapper) tail:AndOperand?
  {
    return new AndExpression($expression, $tail);
  }

AndOperand
  = AndOperator operand:(NotExpression / Wrapper) tail:AndOperand?
  {
    return new AndOperand($operand, $tail);
  }

AndOperator '"AND", "&"'
  = (_+ 'AND'i _+)
  / (_* '&' _*)
  / _+

NotExpression
  = NotOperator expression:Wrapper
  {
    return new NotExpression($expression);
  }

NotOperator '"NOT", "-", "!"'
  = ('NOT'i _+)
  / ([!-] _*)

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
  / FieldAttachment

Field
  = field:('author'i / 'body'i / 'category'i / 'elabid'i / 'group'i / 'status'i / 'title'i / 'visibility'i) ':' strict:('s:' {return true;})? term:(List / LiteralInField)
  {
    return new Field($field, $term, $strict);
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
  = operator:$([<>] '='? / '!'? '=' )? date:Date
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
  = [.,/-]

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
  = field:('locked'i / 'timestamped'i) ':' term:Boolean
  {
    return new Field($field, new SimpleValueWrapper($term));
  }

// return strings because SimpleValueWrapper() takes strings
Boolean
  = ('0' / 'false'i / 'no'i / 'off'i)
  {
    return '0';
  }
  / ('1' / 'true'i / 'yes'i / 'on'i)
  {
    return '1';
  }

// return strings because SimpleValueWrapper() takes strings
FieldRating
  = 'rating'i ':' term:($([0-5]) / 'unrated'i { return '0';})
  {
    return new Field('rating', new SimpleValueWrapper($term));
  }

FieldAttachment
  = 'attachment'i ':' strict:('s:' {return true;})? term:(
    bool:Boolean
      {
        return new SimpleValueWrapper($bool);
      }
    / terms:(List / LiteralInField)
      {
        return $terms;
      }
  )
  {
    return new Field('attachment', $term, $strict);
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
    / Escape
  )+
  {
    return join("", $chars);
  }

LiteralInField 'term'
  = literal:String
  {
    return new SimpleValueWrapper($literal);
  }

Literal 'term'
  = literal:String
  // Prevent operators being a term themselves
  !{
    $l = strtolower($literal);
    return $l === 'not'
      || $l === 'and'
      || $l === 'or';
  }
  {
    return new SimpleValueWrapper($literal);
  }

String
  = chars:(
    [^\n\r\f\\"'|&!() -]
    / Escape
  )+
  {
    return join("", $chars);
  }

Escape
  = $('\\' [%_]) // Escape MySQL wildcard characters
  / '\\' {return '\\\\';} // Search for literal slash by default

Digit
  = [0-9]

Digit19
  = [1-9]

_ 'whitespace'
  = [\t\n\r ]
