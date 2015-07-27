<?php

namespace JMS\Parser;

class AbstractLexerTest extends \PHPUnit_Framework_TestCase
{
    const T_UNKNOWN = 0;
    const T_STRING = 1;
    const T_INTEGER = 2;

    const T_COMMA = 100;

    private $lexer;

    public function testTokenization()
    {
        $this->lexer->setInput('"foo"  1234');

        $this->assertNull($this->lexer->token);
        $this->assertNotNull($this->lexer->next);

        $this->assertAttributeEquals(array(
            array('foo', 0, self::T_STRING),
            array(1234, 7, self::T_INTEGER),
        ), 'tokens', $this->lexer);
    }

    public function testMoveNext()
    {
        $this->lexer->setInput('1 2 3');
        $this->assertNull($this->lexer->token);

        $this->assertTrue($this->lexer->moveNext());
        $this->assertValue(1, $this->lexer->token);

        $this->assertTrue($this->lexer->moveNext());
        $this->assertValue(2, $this->lexer->token);

        $this->assertFalse($this->lexer->moveNext());
        $this->assertValue(3, $this->lexer->token);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSkipUntilWithNonExistent()
    {
        $this->lexer->setInput('1 2 3');
        $this->lexer->skipUntil(self::T_STRING);
    }

    public function testSkipUntil()
    {
        $this->lexer->setInput('1 "foo"');
        $this->assertNull($this->lexer->skipUntil(self::T_STRING));
        $this->assertValue(1, $this->lexer->token);
        $this->assertValue('foo', $this->lexer->next);
    }

    public function testIsNext()
    {
        $this->lexer->setInput('1');
        $this->assertTrue($this->lexer->isNext(self::T_INTEGER));
        $this->assertFalse($this->lexer->isNext(self::T_COMMA));
    }

    public function testIsNextAny()
    {
        $this->lexer->setInput('1');
        $this->assertTrue($this->lexer->isNextAny(array(self::T_COMMA, self::T_INTEGER)));
        $this->assertFalse($this->lexer->isNextAny(array(self::T_COMMA, self::T_STRING)));
    }

    public function testPeek()
    {
        $this->lexer->setInput('1 2 3');

        $this->assertValue(1, $this->lexer->next);
        $this->assertValue(2, $this->lexer->peek()->get());

        $this->assertValue(1, $this->lexer->next);
        $this->assertValue(3, $this->lexer->peek()->get());

        $this->assertValue(1, $this->lexer->next);
        $this->assertTrue($this->lexer->moveNext());
        $this->assertValue(2, $this->lexer->next);
        $this->assertValue(3, $this->lexer->peek()->get());
        $this->assertValue(2, $this->lexer->next);
    }

    private function assertValue($expected, $actualToken)
    {
        $this->assertNotNull($actualToken);
        $this->assertSame($expected, $actualToken[0]);
    }

    protected function setUp()
    {
        $this->lexer = $this->getMockForAbstractClass('JMS\Parser\AbstractLexer');
        $this->lexer->expects($this->any())
                ->method('getRegex')
                ->will($this->returnValue('/("(?:[^"]*|(?<=\\)"))*")|([0-9]+)|\s+|(.)/i'));
        $this->lexer->expects($this->any())
                ->method('determineTypeAndValue')
                ->will($this->returnCallback(function($value) {
                    if (',' === $value) {
                        return array(AbstractLexerTest::T_COMMA, $value);
                    }

                    if ('"' === $value[0]) {
                        return array(AbstractLexerTest::T_STRING, substr($value, 1, -1));
                    }

                    if (preg_match('/^[0-9]+$/', $value)) {
                        return array(AbstractLexerTest::T_INTEGER, (integer) $value);
                    }

                    return array(AbstractLexerTest::T_UNKNOWN, $value);
                }));
    }
}
