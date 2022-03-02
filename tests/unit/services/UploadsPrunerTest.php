<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\CreateUpload;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class UploadsPrunerTest extends \PHPUnit\Framework\TestCase
{
    public function testCleanup(): void
    {
        // create an upload that we will delete
        $Experiments = new Experiments(new Users(1, 1), 1);
        $uploadId = $Experiments->Uploads->create(new CreateUpload('to_delete.sql', dirname(__DIR__, 2) . '/_data/dummy.sql'));
        $Experiments->Uploads->setId($uploadId);
        $Experiments->Uploads->destroy();

        $Cleaner = new UploadsPruner();
        $this->assertEquals(1, $Cleaner->cleanup());
    }
}
