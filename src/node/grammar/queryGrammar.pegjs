/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

or_expression
  = _* expression:and_expression tail:(_+ tail:or {return $tail;})?
    {
      return new OrExpression($expression, $tail);
    }

or
  = orOp operand:and_expression tail:(_+ tail:or {return $tail;})?
    {
      return new OrOperand($operand, $tail);
    }

and_expression
  = expression:not tail:(_+ tail:and {return $tail;})?
    {
      return new AndExpression($expression, $tail);
    }

and
  = andOp? operand:not tail:(_+ tail:and {return $tail;})?
    {
      return new AndOperand($operand, $tail);
    }

not
  = notOp expression:wrapper
    {
      return new NotExpression($expression);
    }
  / e:wrapper
    {
      return $e;
    }

wrapper
  = Parenthesis
  / Field_Metadata
  / Field_Date
  / Field_Rating
  / Field_Boolean
  / Field
  / List
  / Literal

andOp '"AND", "&"'
  = $('AND'i_+)
  / $('&'_*)

orOp '"OR", "|"'
  = $('OR'i_+)
  / $('|'_*)

notOp '"NOT", "!"'
  = $('NOT'i_+)
  / $('!'_*)

Parenthesis 'expression in parenthesis'
  = '(' e:or_expression _* ')'
    {
      return $e;
    }

Field
  = field:('author'i/'body'i/'category'i/'elabid'i/'status'i/'title'i/'visibility'i) ':' term:(List/Literal) // /'tag'i
  {
    return new Field($field, $term);
  }

Field_Date
  = 'date'i ':' date:(Date_Between / Date_Simple)
  {
    return new DateValueWrapper($date);
  }

Date_Between
  = dateFrom:Date '..' dateTo:Date
  {
    return array(
      'type' => 'range',
      'date' => $dateFrom,
      'dateTo' => $dateTo,
    );
  }

Date_Simple
  = operator:$('<=' / '<' / '>=' / '>' / '=' / '!=')? date:Date
  {
    return array(
      'type' => 'simple',
      'operator' => $operator,
      'date' => $date,
    );
  }

Date
  = year:YYYY Date_Separator? month:MM Date_Separator? day:DD
  {
    return $year . $month . $day;
  }

Date_Separator
  = '-'
  / '/'
  / '.'
  / ','

YYYY
  = year:$(digit digit digit digit)
  {
    return $year;
  }

MM
  = month:$('0' digit19 / '1' [0-2] )
  {
    return $month;
  }

DD
  = day:$('0' digit19 / [1-2] digit / '3' [01])
  {
    return $day;
  }

Field_Boolean
  = field:('locked'i/'timestamped'i/'attachment'i) ':' term:Boolean
  {
    return new Field($field, new SimpleValueWrapper($term));
  }

Boolean
  = ('0' / 'false' / 'no' / 'off')
  {
    return 0;
  }
  / ('1' / 'true' / 'yes' / 'on' / '')
  {
    return 1;
  }

Field_Rating
  = 'rating'i ':' term:([0-5] / 'unrated'i { return 0;})
  {
    return new Field('rating', new SimpleValueWrapper($term));
  }

Field_Metadata
  = 'metadata'i ':' key:chars ':' value:(List/Literal)
  {
    return new Metadata($key, $value);
  }
  
chars
  = chars:char+
  {
    return join("", $chars);
  }

char
  // In the original JSON grammar: "any-Unicode-character-except-"-or-\-or-control-character"
  = [^"\\\0-\x1F\x7F\x3A]
  / '\\"' { return '"'; }
  / "\\\\" { return "\\"; }
  / "\\/" { return "/"; }
  / "\\b" { return "\b"; }
  / "\\f" { return "\f"; }
  / "\\n" { return "\n"; }
  / "\\r" { return "\r"; }
  / "\\t" { return "\t"; }
  / "\\u" digits:$(hex hex? hex? hex? hex? hex?) {
    return chr_unicode(intval($digits, 16));
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
        return "";
      }
    / escape
  )+
    {
      return join("", $chars);
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
        return "";
      }
    / escape
  )+
    {
      return join("", $chars);
    }

Literal 'term'
  = !orOp !andOp !notOp literal:$(String)
    {
      return new SimpleValueWrapper($literal);
    }

String
  = chars:(
    [^\n\r\f\\"\\'() ]
    / '\\' nl
      {
        return "";
      }
    / escape
  )+
    {
      return join("", $chars);
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

digit
  = [0-9]

digit19
  = [1-9]

nl
  = '\n'
  / '\r\n'
  / '\r'
  / '\f'

_ 'whitespace'
  = [\t ] //\n\r
