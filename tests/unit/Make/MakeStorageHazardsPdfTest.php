<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Models\Compounds;
use Elabftw\Models\Links\Compounds2ItemsLinks;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\MpdfProvider;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;

use function str_starts_with;

class MakeStorageHazardsPdfTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testGenerate(): void
    {
        $requester = new Users(1, 1);
        $StorageUnits = new StorageUnits($requester, false);
        $roomId = $StorageUnits->create('Hazards pdf room');
        $StorageUnits->setId($roomId);
        $Compounds = new Compounds(new HttpGetter(new Client()), $requester, new NullFingerprinter(), false);
        $compoundId = $Compounds->create(name: 'hazards pdf compound', isCorrosive: true, isNano: true, ghsStatements: array('H314'));
        $Item = $this->getFreshItem();
        new Compounds2ItemsLinks($Item, $compoundId)->create();
        new Containers2ItemsLinks($Item, $roomId)->createWithQuantity(1.0, 'L');

        $MakePdf = new MakeStorageHazardsPdf(new MpdfProvider('Toto'), $StorageUnits);
        $this->assertTrue(str_starts_with($MakePdf->getFileContent(), '%PDF'));
        $this->assertSame('hazards-Hazards-pdf-room.pdf', $MakePdf->getFileName());
    }
}
