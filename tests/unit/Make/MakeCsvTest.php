<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\EntitySlug;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users;

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    private MakeCsv $Make;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        $this->Make = new MakeCsv(
            $requester,
            array(new EntitySlug(EntityType::Experiments, 1), new EntitySlug(EntityType::Items, 2))
        );
    }

    public function testGetFileName(): void
    {
        $this->assertTrue(str_ends_with($this->Make->getFileName(), 'export.elabftw.csv'));
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('text/csv; charset=UTF-8', $this->Make->getContentType());
    }
}
