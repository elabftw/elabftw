Parser Library
==============

This library allows you to easily implement recursive-descent parsers.

Installation
------------
You can install this library through composer:

.. code-block :: bash

    composer require jms/parser-lib

or add it to your ``composer.json`` file directly.

Example
-------
Let's assume that you would like to write a parser for a calculator. For simplicity
sake, we will assume that the parser would already return the result of the
calculation. Inputs could look like this ``1 + 1`` and we would expect ``2`` as
a result.

The first step, is to create a lexer which breaks the input string up into
individual tokens which can then be consumed by the parser. This library provides
a convenient class for simple problems which we will use::

    $lexer = new \JMS\Parser\SimpleLexer(
        '/
            # Numbers
            ([0-9]+)

            # Do not surround with () because whitespace is not meaningful for
            # our purposes.
            |\s+

            # Operators; we support only + and -
            |(+)|(-)
        /x', // The x modifier tells PCRE to ignore whitespace in the regex above.

        // This maps token types to a human readable name.
        array(0 => 'T_UNKNOWN', 1 => 'T_INT', 2 => 'T_PLUS', 3 => 'T_MINUS'),

        // This function tells the lexer which type a token has. The first element is
        // an integer from the map above, the second element the normalized value.
        function($value) {
            if ('+' === $value) {
                return array(2, '+');
            }
            if ('-' === $value) {
                return array(3, '-');
            }
            if (is_numeric($value)) {
                return array(1, (integer) $value);
            }

            return array(0, $value);
        }
    );

Now the second step, is to create the parser which can consume the tokens once
the lexer has split them::

    class MyParser extends \JMS\Parser\AbstractParser
    {
        const T_UNKNOWN = 0;
        const T_INT = 1;
        const T_PLUS = 2;
        const T_MINUS = 3;

        public function parseInternal()
        {
            $result = $this->match(self::T_INT);

            while ($this->lexer->isNextAny(array(self::T_PLUS, self::T_MINUS))) {
                if ($this->lexer->isNext(self::T_PLUS)) {
                    $this->lexer->moveNext();
                    $result += $this->match(self::T_INT);
                } else if ($this->lexer->isNext(self::T_MINUS)) {
                    $this->lexer->moveNext();
                    $result -= $this->match(self::T_INT);
                } else {
                    throw new \LogicException('Previous ifs were exhaustive.');
                }
            }

            return $result;
        }
    }

    $parser = new MyParser($lexer);
    $parser->parse('1 + 1'); // int(2)
    $parser->parse('5 + 10 - 4'); // int(11)

That's it. Now you can perform basic operations already. If you like you can now
also replace the hard-coded integers in the lexer with the class constants of the
parser.

License
-------

The code is released under the business-friendly `Apache2 license`_.

Documentation is subject to the `Attribution-NonCommercial-NoDerivs 3.0 Unported
license`_.

.. _Apache2 license: http://www.apache.org/licenses/LICENSE-2.0.html
.. _Attribution-NonCommercial-NoDerivs 3.0 Unported license: http://creativecommons.org/licenses/by-nc-nd/3.0/

