<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class MakePdfTest extends \PHPUnit\Framework\TestCase
{
    private MakePdf $MakePdf;

    protected function setUp(): void
    {
        $Entity = new Experiments(new Users(1, 1), 1);
        $Entity->canOrExplode('read');
        // add invalid tex macro to body to cover notification being created upon failing mathjax
        $Entity->entityData['body'] .= '\n<p>$ \someInvalidTexMacro $</p>';
        // test >Append attached PDFs<
        $Entity->Users->userData['append_pdfs'] = true;
        $MpdfProvider = new MpdfProvider('Toto');
        $this->MakePdf = new MakePdf($MpdfProvider, $Entity);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('application/pdf', $this->MakePdf->getContentType());
    }
}
