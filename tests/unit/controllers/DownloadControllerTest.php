<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Enums\Storage;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadControllerTest extends \PHPUnit\Framework\TestCase
{
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = Storage::MEMORY->getStorage()->getFs();
    }

    public function testGetResponse(): void
    {
        $longName = 'aa/aabb.txt';
        $this->fs->write($longName, 'blah');
        $DownloadController = new DownloadController($this->fs, $longName);
        $this->assertInstanceOf(StreamedResponse::class, $DownloadController->getResponse());
    }

    public function testGetResponseAgain(): void
    {
        $longName = 'aa/aacc.docx';
        $this->fs->write($longName, 'blah');
        $DownloadController = new DownloadController($this->fs, $longName);
        $Response = $DownloadController->getResponse();
        // because we're dealing with a streamed response, this workaround is needed to get content
        ob_start();
        $getContent = $Response->sendContent();
        $getContent = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('blah', $getContent);
    }
}
