<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Models\Compounds;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use const UPLOAD_ERR_OK;

class CompoundsCsvTest extends \PHPUnit\Framework\TestCase
{
    public function testImport(): void
    {
        $requester = new Users(1, 1);
        $Items = new Items($requester);
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/compounds.csv',
            'compounds.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $httpGetter = new HttpGetter(new Client(), '', false);
        $Compounds = new Compounds($httpGetter, $requester, new NullFingerprinter());
        $Import = new CompoundsCsv(new NullOutput(), $Items, $uploadedFile, $Compounds, 1);
        $this->assertEquals(3, $Import->import());
        // importing again will not import anything because of CAS clash
        $this->assertEquals(0, $Import->import());
    }
}
