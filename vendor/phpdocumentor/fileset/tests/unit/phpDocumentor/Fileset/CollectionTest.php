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

use phpDocumentor\Fileset\Collection\IgnorePatterns;

/**
 * Test case for Collection class.
 *
 * @author  Mike van Riel <mike.vanriel@naenius.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    http://phpdoc.org
 */
class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Collection */
    protected $fixture = null;

    protected function setUp()
    {
        $this->fixture = new Collection();
    }

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

    /**
     * Get a count of the files that exist in the test suite's "data" directory.
     * @return int
     */
    protected function getCountOfDataDirFiles()
    {
        return 2;
    }

    /* __construct() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::__construct() */
    public function testConstructorSucceeds()
    {
        $this->assertInstanceOf('\phpDocumentor\Fileset\Collection', $this->fixture);
    }

    /* setIgnorePatterns() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::setIgnorePatterns() */
    public function testSetIgnorePatternsAcceptsValidIgnorePattern()
    {
        $pattern = array('./foo/*');
        $this->fixture->setIgnorePatterns($pattern);
        $this->assertInstanceOf(
            '\phpDocumentor\Fileset\Collection\IgnorePatterns',
            $this->fixture->getIgnorePatterns()
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::setIgnorePatterns() */
    public function testSetIgnorePatternsAcceptsMultipleValidIgnorePatternsGivenAtOnce()
    {
        $pattern = array('./foo/*', '*/bar/*.txt');
        $this->fixture->setIgnorePatterns($pattern);
        $this->assertInstanceOf(
            '\phpDocumentor\Fileset\Collection\IgnorePatterns',
            $this->fixture->getIgnorePatterns()
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::setIgnorePatterns() */
    public function testSetIgnorePatternsAcceptsMultipleValidIgnorePatternsGivenSeparately()
    {
        $pattern1 = array('./foo/*');
        $pattern2 = array('*/bar/*.txt');
        $this->fixture->setIgnorePatterns($pattern1);
        $this->fixture->setIgnorePatterns($pattern2);
        $this->assertInstanceOf(
            '\phpDocumentor\Fileset\Collection\IgnorePatterns',
            $this->fixture->getIgnorePatterns()
        );
    }

    /* getIgnorePatterns() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::getIgnorePatterns() */
    public function testGetIgnorePatternsAcceptsValidIgnorePattern()
    {
        $pattern = array('./foo/*');
        $this->fixture->setIgnorePatterns($pattern);
        $this->assertInstanceOf(
            '\phpDocumentor\Fileset\Collection\IgnorePatterns',
            $this->fixture->getIgnorePatterns()
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::getIgnorePatterns() */
    public function testGetIgnorePatternsAcceptsMultipleValidIgnorePatternsGivenAtOnce()
    {
        $pattern = array('./foo/*', '*/bar/*.txt');
        $this->fixture->setIgnorePatterns($pattern);
        $this->assertInstanceOf(
            '\phpDocumentor\Fileset\Collection\IgnorePatterns',
            $this->fixture->getIgnorePatterns()
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::getIgnorePatterns() */
    public function testGetIgnorePatternsAcceptsMultipleValidIgnorePatternsGivenSeparately()
    {
        $pattern1 = array('./foo/*');
        $pattern2 = array('*/bar/*.txt');
        $this->fixture->setIgnorePatterns($pattern1);
        $this->fixture->setIgnorePatterns($pattern2);
        $this->assertInstanceOf(
            '\phpDocumentor\Fileset\Collection\IgnorePatterns',
            $this->fixture->getIgnorePatterns()
        );
    }

    /* setAllowedExtensions() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::setAllowedExtensions() */
    public function testSetAllowedExtensionsAcceptsEmptyArray()
    {
        $this->fixture->setAllowedExtensions(array());
        $this->assertTrue(true, 'Test passed');
    }

    /** @covers \phpDocumentor\Fileset\Collection::setAllowedExtensions() */
    public function testSetAllowedExtensionsAcceptsSimpleArray()
    {
        $this->fixture->setAllowedExtensions(array('php'));
        $this->assertTrue(true, 'Test passed');
    }

    /** @covers \phpDocumentor\Fileset\Collection::setAllowedExtensions() */
    public function testSetAllowedExtensionsAcceptsAssociativeArray()
    {
        $this->fixture->setAllowedExtensions(array('PHP executable file' => 'php'));
        $this->assertTrue(true, 'Test passed');
    }

    /** @covers \phpDocumentor\Fileset\Collection::setAllowedExtensions() */
    public function testSetAllowedExtensionsAllowsMultipleCalls()
    {
        $this->fixture->setAllowedExtensions(array('PHP executable file' => 'php'));
        $this->fixture->setAllowedExtensions(array('PHP3 old file' => 'php3'));
        $this->assertTrue(true, 'Test passed');
    }

    /* addAllowedExtension() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::addAllowedExtension() */
    public function testAddAllowedExtensionAcceptsEmptyString()
    {
        $extension = '';
        $this->fixture->addAllowedExtension($extension);
        $this->assertTrue(true, 'Test passed');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addAllowedExtension() */
    public function testAddAllowedExtensionAcceptsSingleString()
    {
        $extension = 'php';
        $this->fixture->addAllowedExtension($extension);
        $this->assertTrue(true, 'Test passed');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addAllowedExtension() */
    public function testAddAllowedExtensionAllowsMultipleCalls()
    {
        $this->fixture->addAllowedExtension('php');
        $this->fixture->addAllowedExtension('php3');
        $this->assertTrue(true, 'Test passed');
    }

    /* addDirectories() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::addDirectories() */
    public function testAddDirectoriesWhenGivenEmptyArrayDoesNothing()
    {
        $this->fixture->addDirectories(array());
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectories() */
    public function testAddDirectoriesWhenGivenOnePathSucceeds()
    {
        $this->fixture->addDirectories(array($this->getNameOfDataDir()));
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectories() */
    public function testAddDirectoriesWhenGivenTheSamePathTwiceIsOk()
    {
        $this->fixture->addDirectories(array($this->getNameOfDataDir()));
        $this->fixture->addDirectories(array($this->getNameOfDataDir()));
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectories() */
    public function testAddDirectoriesWhenGivenManyPathsSucceeds()
    {
        $this->fixture->addDirectories(
            array(
                $this->getNameOfDataDir(),
                $this->getNameOfDataDir() . '.',
                $this->getNameOfDataDir() . '..',
        ));
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectories() */
    public function testAddDirectoriesWhenGivenTheSamePathTwiceDoesNotResultInDuplicatedFilenames()
    {
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->addDirectories(array($this->getNameOfDataDir()));
        $this->fixture->addDirectories(array($this->getNameOfDataDir()));

        $files = $this->fixture->getFilenames();

        $this->assertEquals($this->getCountOfDataDirFiles(), count($files));
    }

    /* addDirectory() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::addDirectory() */
    public function testAddDirectoryCanSeePharContents()
    {
        // read the phar test fixture
        $this->fixture->addDirectory(
            'phar://'. $this->getNameOfDataDir() . 'test.phar'
        );

        // we know which files are in there; test against it
        $this->assertEquals(
            array(
                'phar://' . $this->getNameOfDataDir() . 'test.phar' . DIRECTORY_SEPARATOR . 'folder' . DIRECTORY_SEPARATOR . 'test.php',
                'phar://' . $this->getNameOfDataDir() . 'test.phar' . DIRECTORY_SEPARATOR . 'test.php',
            ),
            $this->fixture->getFilenames()
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectory() */
    public function testAddDirectoryCanSeeDirectoryContents()
    {
        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));
        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();
        $count = count($files);

        // do a few checks to see if it has caught some cases
        $this->assertGreaterThan(0, $count);
        $this->assertContains(
                realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectory() */
    public function testAddDirectoryWhenAllowedExtensionsHidesEverything()
    {
        // there are no files that match the *default* extensions
        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();

        // this file should have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'fileWithText.txt'),
            $files
        );

        // this file should also have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectory() */
    public function testAddDirectoryWhenIgnorePatternHidesEverything()
    {
        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->getIgnorePatterns()->append('(phar|txt)');

        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();

        // this file should have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'fileWithText.txt'),
            $files
        );

        // this file should also have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectory() */
    public function testAddDirectoryWhenIgnorePatternHidesSomething()
    {
        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->getIgnorePatterns()->append('(phar)');

        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();

        // this file should have been seen
        $this->assertContains(
            realpath($this->getNameOfDataDir() . 'fileWithText.txt'),
            $files
        );

        // this file should have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::addDirectory() */
    public function testAddDirectoryWhenFollowSymlinksIsSet()
    {
        /*
         * NOTE:
         *     this test does not verify that any symlinks were followed...
         *     it simply exercises the $finder->followLinks() line.
         */
        $this->fixture->setFollowSymlinks(true);

        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->getIgnorePatterns()->append('(phar)');

        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();

        // this file should have been seen
        $this->assertContains(
            realpath($this->getNameOfDataDir() . 'fileWithText.txt'),
            $files
        );

        // this file should have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /* addFiles() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::addFiles() */
    public function testAddFilesWhenGivenEmptyArrayDoesNothing()
    {
        $this->fixture->addFiles(array());
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFiles() */
    public function testAddFilesWhenGivenOnePathSucceeds()
    {
        $this->fixture->addFiles(array($this->getNameOfDataDir() . 'test.phar'));
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFiles() */
    public function testAddFilesWhenGivenTheSamePathTwiceIsOk()
    {
        $this->fixture->addFiles(array($this->getNameOfDataDir() . 'test.phar'));
        $this->fixture->addFiles(array($this->getNameOfDataDir() . 'test.phar'));
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFiles() */
    public function testAddFilesWhenGivenManyPathsSucceeds()
    {
        $this->fixture->addFiles(
            array(
                $this->getNameOfDataDir() . 'test.phar',
                $this->getNameOfDataDir() . 'fileWithText.txt',
            ));
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFiles() */
    public function testAddFilesWhenGivenTheSamePathTwiceDoesNotResultInDuplicatedFilenames()
    {
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->addFiles(array($this->getNameOfDataDir() . 'test.phar'));
        $this->fixture->addFiles(array($this->getNameOfDataDir() . 'test.phar'));

        $files = $this->fixture->getFilenames();

        $this->assertEquals(1, count($files));
    }

    /* addFile() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::addFile() */
    public function testAddFileWhenGivenEmptyStringThrowsException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected filename or object of type SplFileInfo but received nothing at all'
        );
        $this->fixture->addFile('');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFile() */
    public function testAddFileWhenGivenOnePathSucceeds()
    {
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFile() */
    public function testAddFileWhenGivenTheSamePathTwiceIsOk()
    {
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::addFile() */
    public function testAddFileWhenGivenTheSamePathTwiceDoesNotResultInDuplicatedFilenames()
    {
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');

        $files = $this->fixture->getFilenames();

        $this->assertEquals(1, count($files));
    }

    /* getGlobbedPaths() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::getGlobbedPaths() */
    public function testGetGlobbedPathsWhenGivenEmptyStringThrowsException()
    {
        $this->setExpectedException(
                        '\InvalidArgumentException',
                        'Expected filename or object of type SplFileInfo but received nothing at all'
        );
        $this->fixture->addFile('');
    }

    /** @covers \phpDocumentor\Fileset\Collection::getGlobbedPaths() */
    public function testGetGlobbedPathsWhenGivenOnePathSucceeds()
    {
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::getGlobbedPaths() */
    public function testGetGlobbedPathsWhenGivenTheSamePathTwiceIsOk()
    {
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->assertTrue(true, 'Test passes');
    }

    /** @covers \phpDocumentor\Fileset\Collection::getGlobbedPaths() */
    public function testGetGlobbedPathsWhenGivenTheSamePathTwiceDoesNotResultInDuplicatedFilenames()
    {
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');

        $files = $this->fixture->getFilenames();

        $this->assertEquals(1, count($files));
    }

    /* getFilenames() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::getFilenames() */
    public function testGetFilenamesCanSeePharContents()
    {
        // read the phar test fixture
        $this->fixture->addDirectory(
            'phar://'. $this->getNameOfDataDir() . 'test.phar'
        );

        // we know which files are in there; test against it
        $this->assertEquals(
            array(
                'phar://' . $this->getNameOfDataDir() . 'test.phar' . DIRECTORY_SEPARATOR . 'folder' . DIRECTORY_SEPARATOR . 'test.php',
                'phar://' . $this->getNameOfDataDir() . 'test.phar' . DIRECTORY_SEPARATOR . 'test.php',
            ),
            $this->fixture->getFilenames()
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::getFilenames() */
    public function testGetFilenamesCanSeeDirectoryContents()
    {
        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));
        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();
        $count = count($files);

        // do a few checks to see if it has caught some cases
        $this->assertGreaterThan(0, $count);
        $this->assertContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::getFilenames() */
    public function testGetFilenamesWhenIgnorePatternHidesEverything()
    {
        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->getIgnorePatterns()->append('(phar|txt)');

        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();

        // this file should have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'fileWithText.txt'),
            $files
        );

        // this file should also have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }

    /** @covers \phpDocumentor\Fileset\Collection::getFilenames() */
    public function testGetFilenamesWhenIgnorePatternHidesSomething()
    {
        // load the data test folder... must add non-default extensions first
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));

        $this->fixture->getIgnorePatterns()->append('(phar)');

        $this->fixture->addDirectory($this->getNameOfDataDir());
        $files = $this->fixture->getFilenames();

        // this file should have been seen
        $this->assertContains(
            realpath($this->getNameOfDataDir() . 'fileWithText.txt'),
            $files
        );

        // this file should have been ignored
        $this->assertNotContains(
            realpath($this->getNameOfDataDir() . 'test.phar'),
            $files
        );
    }


    /* getProjectRoot() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::getProjectRoot() */
    public function testGetProjectRootWhenNothingHasYetBeenAddedReturnsRootSlash()
    {
        $this->assertEquals(DIRECTORY_SEPARATOR, $this->fixture->getProjectRoot());
    }

    /** @covers \phpDocumentor\Fileset\Collection::getProjectRoot() */
    public function testGetProjectRootWhenOneDatafileWasAddedReturnsDataFolder()
    {
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');

        // realpath() steals our trailing directory separator
        $expected = realpath($this->getNameOfDataDir()) . DIRECTORY_SEPARATOR;
        $this->assertEquals($expected, $this->fixture->getProjectRoot());
    }

    /** @covers \phpDocumentor\Fileset\Collection::getProjectRoot() */
    public function testGetProjectRootWhenTwoDatafilesWereAddedReturnsDataFolder()
    {
        $this->fixture->setAllowedExtensions(array('phar', 'txt'));
        $this->fixture->addFile($this->getNameOfDataDir() . 'test.phar');
        $this->fixture->addFile($this->getNameOfDataDir() . 'fileWithText.txt');

        // realpath() steals our trailing directory separator
        $expected = realpath($this->getNameOfDataDir()) . DIRECTORY_SEPARATOR;
        $this->assertEquals($expected, $this->fixture->getProjectRoot());
    }

    /** @covers \phpDocumentor\Fileset\Collection::getProjectRoot() */
    public function testGetProjectRootWhenTwoFilesAreApart()
    {
        $this->fixture->addFile(__FILE__);
        $this->fixture->addFile($this->getNameOfDataDir() . 'fileWithText.txt');

        // realpath() steals our trailing directory separator
        $expected =
            realpath(
                $this->getNameOfDataDir()
                . '..'
            )
            . DIRECTORY_SEPARATOR
        ;
        $this->assertEquals($expected, $this->fixture->getProjectRoot());
    }

    /** @covers \phpDocumentor\Fileset\Collection::getProjectRoot() */
    public function testGetProjectRootWhenTwoFilesAreVeryFarApart()
    {
        $this->fixture->addFile(__FILE__);
        $this->fixture->addFile(
            $this->getNameOfDataDir()
            . '..' . DIRECTORY_SEPARATOR
            . '..' . DIRECTORY_SEPARATOR
            . 'src' . DIRECTORY_SEPARATOR
            . 'phpDocumentor' . DIRECTORY_SEPARATOR
            . 'Fileset' . DIRECTORY_SEPARATOR
            . 'Collection.php'
        );

        // realpath() steals our trailing directory separator
        $expected =
        realpath(
            $this->getNameOfDataDir()
            . '..' . DIRECTORY_SEPARATOR
            . '..' . DIRECTORY_SEPARATOR
        )
        . DIRECTORY_SEPARATOR
        ;
        $this->assertEquals($expected, $this->fixture->getProjectRoot());
    }

    /* setIgnoreHidden() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::setIgnoreHidden() */
    public function testSetIgnoreHiddenGivenFalse()
    {
        /*
         * NOTE:
         *     this does not verify that hidden files were ignored...
         *     it simply exercises the setIgnoreHidden() method.
         */
        $this->fixture->setIgnoreHidden(false);
        $this->assertFalse($this->fixture->getIgnoreHidden());
    }

    /** @covers \phpDocumentor\Fileset\Collection::setIgnoreHidden() */
    public function testSetIgnoreHiddenGivenTrue()
    {
        /*
         * NOTE:
         *     this does not verify that hidden files were not ignored...
         *     it simply exercises the setIgnoreHidden() method.
         */
        $this->fixture->setIgnoreHidden(true);
        $this->assertTrue($this->fixture->getIgnoreHidden());
    }

    /* getIgnoreHidden() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::getIgnoreHidden() */
    public function testGetIgnoreHiddenGivenFalse()
    {
        /*
         * NOTE:
         *     this does not verify that hidden files were ignored...
         *     it simply exercises the getIgnoreHidden() method.
         */
        $this->fixture->setIgnoreHidden(false);
        $this->assertFalse($this->fixture->getIgnoreHidden());
    }

    /** @covers \phpDocumentor\Fileset\Collection::getIgnoreHidden() */
    public function testGetIgnoreHiddenGivenTrue()
    {
        /*
         * NOTE:
         *     this does not verify that hidden files were not ignored...
         *     it simply exercises the getIgnoreHidden() method.
         */
        $this->fixture->setIgnoreHidden(true);
        $this->assertTrue($this->fixture->getIgnoreHidden());
    }

    /* setFollowSymlinks() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::setFollowSymlinks() */
    public function testSetFollowSymlinksGivenFalse()
    {
        /*
         * NOTE:
        *     this does not verify that symlinks were not followed...
        *     it simply exercises the setFollowSymlinks() method.
        */
        $this->fixture->setFollowSymlinks(false);
        $this->assertFalse($this->fixture->getFollowSymlinks());
    }

    /** @covers \phpDocumentor\Fileset\Collection::setFollowSymlinks() */
    public function testSetFollowSymlinksGivenTrue()
    {
        /*
         * NOTE:
        *     this does not verify that symlinks were followed...
        *     it simply exercises the setFollowSymlinks() method.
        */
        $this->fixture->setFollowSymlinks(true);
        $this->assertTrue($this->fixture->getFollowSymlinks());
    }

    /* getFollowSymlinks() ***************************************************/

    /** @covers \phpDocumentor\Fileset\Collection::getFollowSymlinks() */
    public function testGetFollowSymlinksGivenFalse()
    {
        /*
         * NOTE:
         *     this does not verify that symlinks were not followed...
         *     it simply exercises the getFollowSymlinks() method.
         */
        $this->fixture->setFollowSymlinks(false);
        $this->assertFalse($this->fixture->getFollowSymlinks());
    }

    /** @covers \phpDocumentor\Fileset\Collection::getFollowSymlinks() */
    public function testGetFollowSymlinksGivenTrue()
    {
        /*
         * NOTE:
         *     this does not verify that symlinks were followed...
         *     it simply exercises the getFollowSymlinks() method.
         */
        $this->fixture->setFollowSymlinks(true);
        $this->assertTrue($this->fixture->getFollowSymlinks());
    }
}
