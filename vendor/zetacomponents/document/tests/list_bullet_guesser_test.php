<?php

class ezcDocumentListBulletGuesserTest extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testMarkToCharSuccess()
    {
        $guesser = new ezcDocumentListBulletGuesser();

        $expectedResult = '◼';

        $actualResult = $guesser->markToChar( 'square' );
        
        self::assertEquals(
            $expectedResult,
            $actualResult,
            'Bullet "square" guessed incorrect.'
        );
    }

    public function testMarkToCharUnknownSuccess()
    {
        $guesser = new ezcDocumentListBulletGuesser();

        $expectedResult = '⚫';

        $actualResult = $guesser->markToChar( 'unknown' );
        
        self::assertEquals(
            $expectedResult,
            $actualResult,
            'Bullet "unknown" guessed incorrect.'
        );
    }

    public function testMarkToCharUnknownCustomDefaultSuccess()
    {
        $guesser = new ezcDocumentListBulletGuesser();

        $expectedResult = 'a';

        $actualResult = $guesser->markToChar( 'unknown', 'a' );
        
        self::assertEquals(
            $expectedResult,
            $actualResult,
            'Bullet "unknown" guessed incorrect.'
        );
    }

    public function testMarkToCharNoGuessSuccess()
    {
        $guesser = new ezcDocumentListBulletGuesser();

        $expectedResult = 'a';

        $actualResult = $guesser->markToChar( 'a' );
        
        self::assertEquals(
            $expectedResult,
            $actualResult,
            'Bullet "a" converted incorrect.'
        );
    }
}


?>
