<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HandlerTest extends \PHPUnit\Framework\TestCase
{
    private Handler $handler;

    protected function setUp(): void
    {
        $this->handler = new Handler(new Users(1, 1), new ConsoleLogger(new ConsoleOutput()));
    }

    public function testRead(): void
    {
        $res = $this->handler->readAll();
        $this->assertEquals(209715200, $res['max_filesize']);
    }

    public function testPostCsv(): void
    {
        $req = array(
            'file' => new UploadedFile(
                dirname(__DIR__, 2) . '/_data/importable-chem.csv',
                'importable.csv',
                null,
                UPLOAD_ERR_OK,
                true,
            ),
            'category' => 1,
            'entity_type' => 'items',
            'owner' => 2,
        );

        $this->assertEquals(13, $this->handler->postAction(Action::Update, $req));
    }

    public function testPostEln(): void
    {
        $req = array(
            'file' => new UploadedFile(
                dirname(__DIR__, 2) . '/_data/multiple-experiments.eln',
                'importable.eln',
                null,
                UPLOAD_ERR_OK,
                true,
            ),
            'category' => 1,
            'entity_type' => 'experiments',
            'owner' => 2,
        );
        $this->assertEquals(9, $this->handler->postAction(Action::Update, $req));
    }

    public function testPostInvalidExtension(): void
    {
        $req = array(
            'file' => new UploadedFile(
                dirname(__DIR__, 2) . '/_data/importable-chem.csv',
                'nope.zip',
                null,
                UPLOAD_ERR_OK,
                true,
            ),
            'category' => 1,
            'entity_type' => 'items',
        );
        $this->expectException(ImproperActionException::class);
        $this->handler->postAction(Action::Update, $req);
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->handler->patch(Action::Update, array());
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/import/', $this->handler->getApiPath());
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->handler->destroy();
    }
}
