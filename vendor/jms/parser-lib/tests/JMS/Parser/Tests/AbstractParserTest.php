<?php

namespace JMS\Parser\Tests;

class AbstractParserTest extends \PHPUnit_Framework_TestCase
{
    const T_UNKNOWN = 0;
    const T_INT = 1;
    const T_PLUS = 100;
    const T_MINUS = 101;

    private $parser;
    private $lexer;

    public function testParse()
    {
        $this->assertSame(2, $this->parser->parse('1 + 1'));
        $this->assertSame(5, $this->parser->parse('1 + 1 + 4 - 1'));
    }

    /**
     * @expectedException JMS\Parser\SyntaxErrorException
     * @expectedExceptionMessage Expected T_INT, but got end of input.
     */
    public function testUnexpectedEnd()
    {
        $this->parser->parse('1 + ');
    }

    protected function setUp()
    {
        $this->lexer = $lexer = new \JMS\Parser\SimpleLexer(
            '/([0-9]+)|\s+|(.)/',
            array(0 => 'T_UNKNOWN', 1 => 'T_INT', 100 => 'T_PLUS', 101 => 'T_MINUS'),
            function($value) {
                if ('+' === $value) {
                    return array(AbstractParserTest::T_PLUS, $value);
                }
                if ('-' === $value) {
                    return array(AbstractParserTest::T_MINUS, $value);
                }

                // We would loose information on doubles here, but for this test it
                // does not matter anyway.
                if (is_numeric($value)) {
                    return array(AbstractParserTest::T_INT, (integer) $value);
                }

                return AbstractParserTest::T_UNKNOWN;
            }
        );

        $this->parser = $parser = $this->getMockBuilder('JMS\Parser\AbstractParser')
                            ->setConstructorArgs(array($this->lexer))
                            ->getMockForAbstractClass();

        $match = function($type) use ($parser) {
            $ref = new \ReflectionMethod($parser, 'match');
            $ref->setAccessible(true);

            return $ref->invoke($parser, $type);
        };

        $this->parser->expects($this->any())
                ->method('parseInternal')
                ->will($this->returnCallback(function() use ($lexer, $match) {
                    // Result :== Number ( ("+"|"-") Number )*

                    $result = $match(AbstractParserTest::T_INT);
                    while ($lexer->isNextAny(array(AbstractParserTest::T_PLUS, AbstractParserTest::T_MINUS))) {
                        if ($lexer->isNext(AbstractParserTest::T_PLUS)) {
                            $lexer->moveNext();

                            $result += $match(AbstractParserTest::T_INT);
                        } else if ($lexer->isNext(AbstractParserTest::T_MINUS)) {
                            $lexer->moveNext();

                            $result -= $match(AbstractParserTest::T_INT);
                        } else {
                            throw new \LogicException('Previous ifs were exhaustive.');
                        }
                    }

                    return $result;
                }));
    }
}