<?php
/**
 * phpDocumentor
 *
 * PHP Version 5
 *
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Fileset;

/**
 * Test case for File class.
 *
 * @author  Mike van Riel <mike.vanriel@naenius.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    http://phpdoc.org
 */
class FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Get the pathed name of the test suite's "data" directory.
     *
     * The path includes a trailing directory separator.
     * @return string
     */
    protected function getNameOfDataDir()
    {
        return
            __DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR
        ;
    }

    /* __construct() ******************************/

    /** @covers \phpDocumentor\Fileset\File::__construct() */
    public function testConstructorWithEmptyArgThrowsException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected filename or object of type SplFileInfo but received nothing at all'
        );
        $file = new File('');
    }

    /** @covers \phpDocumentor\Fileset\File::__construct() */
    public function testConstructorWithUnusableObjectThrowsException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected filename or object of type SplFileInfo but received stdClass'
        );
        $file = new File(new \stdClass());
    }

    /** @covers \phpDocumentor\Fileset\File::__construct() */
    public function testConstructorWithEmptySplFileInfoObjectThrowsException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected filename or object of type SplFileInfo but received nothing at all'
        );
        $file = new File(new \SplFileInfo(''));
    }

    /** @covers \phpDocumentor\Fileset\File::__construct() */
    public function testConstructorWithValidSplFileInfoObjectSucceeds()
    {
        $file = new File(new \SplFileInfo('foo.txt'));
        $this->assertInstanceOf('\phpDocumentor\Fileset\File', $file);
    }

    /* getMimeType() ******************************/

    /** @covers \phpDocumentor\Fileset\File::getMimeType() */
    public function testGetMimeTypeWhenFileinfoNotLoadedThrowsException()
    {
        if (true === extension_loaded('fileinfo')) {
            $this->markTestSkipped('Does not apply if fileinfo is loaded.');
        }
        $file = new File(new \SplFileInfo('foo.txt'));
        $this->setExpectedException(
            '\RuntimeException',
            'The finfo extension or mime_content_type function are needed to determine the Mime Type for this file.'
        );
        $file->getMimeType();
    }

    /** @covers \phpDocumentor\Fileset\File::getMimeType() */
    public function testGetMimeTypeWithEmptySplFileInfoObjectThrowsException()
    {
        if (false === extension_loaded('fileinfo')) {
            $this->markTestSkipped('Does not apply if fileinfo is not loaded.');
        }
        $file = new File(new \SplFileInfo('foo.txt'));
        $this->setExpectedException(
            '\RuntimeException',
            'Failed to read file info via finfo'
        );
        $file->getMimeType();
    }

    /** @covers \phpDocumentor\Fileset\File::getMimeType() */
    public function testGetMimeTypeWithValidSplFileInfoObjectOfTestTextFile()
    {
        if (false === extension_loaded('fileinfo')) {
            $this->markTestSkipped('Does not apply if fileinfo is not loaded.');
        }
        $file = new File(new \SplFileInfo($this->getNameOfDataDir() . 'fileWithText.txt'));
        $this->assertEquals('text/plain', $file->getMimeType());
    }

    /* fread() ******************************/

    /** @covers \phpDocumentor\Fileset\File::fread() */
    public function testFreadWithEmptySplFileInfoObjectThrowsException()
    {
        $file = new File(new \SplFileInfo('foo.txt'));
        $this->setExpectedException(
            '\RuntimeException',
            'Unable to open file'
        );
        $file->fread();
    }

    /** @covers \phpDocumentor\Fileset\File::fread() */
    public function testFreadWithValidSplFileInfoObjectOfTestTextFile()
    {
        $file = new File(new \SplFileInfo($this->getNameOfDataDir() . 'fileWithText.txt'));
        $expected = <<<END
one line of text...
another line of text.
END;
        $actual = $file->fread();
        $this->assertEquals($expected, $actual);
    }
}
