<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Codeception\Attribute\DataProvider;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;

use function array_column;
use function bin2hex;
use function count;
use function random_bytes;
use function strtoupper;
use function substr;

class GhsStatementsTest extends \PHPUnit\Framework\TestCase
{
    private GhsStatements $GhsStatements;

    protected function setUp(): void
    {
        $this->GhsStatements = new GhsStatements();
    }

    public function testGetApiPath(): void
    {
        $this->assertSame('api/v2/ghs_statements/', $this->GhsStatements->getApiPath());
    }

    public function testReadAll(): void
    {
        $statements = $this->GhsStatements->readAll();
        $codes = array_column($statements, 'code');
        // hazard first, then EU hazard, then precautionary
        $this->assertSame('H', $statements[0]['kind']);
        $this->assertSame('P', $statements[count($statements) - 1]['kind']);
        foreach (array('H300', 'H350i', 'EUH066', 'EUH380', 'P203', 'P301+P316', 'P370+P380+P375[+P378]') as $code) {
            $this->assertContains($code, $codes);
        }
        // the code is case-sensitive: these are two statements
        $this->assertContains('H360FD', $codes);
        $this->assertContains('H360Fd', $codes);
    }

    public function testReadOneFromCode(): void
    {
        $statement = $this->GhsStatements->readOneFromCode('H300');
        $this->assertSame('H', $statement['kind']);
        $this->assertSame('Fatal if swallowed.', $statement['statement']);
        $this->assertSame('May damage fertility. Suspected of damaging the unborn child.', $this->GhsStatements->readOneFromCode('H360Fd')['statement']);
    }

    /**
     * A statement is added by hand from its code, see CompoundsGhsStatements::postAction()
     */
    #[DataProvider('provideRejectedCodes')]
    public function testReadOneFromCodeRejects(string $code): void
    {
        $this->expectException(ImproperActionException::class);
        $this->GhsStatements->readOneFromCode($code);
    }

    public static function provideRejectedCodes(): array
    {
        return array(
            'unknown' => array('H999'),
            'case sensitive' => array('h300'),
            'withdrawn' => array('P281'),
        );
    }

    public function testLinkToCompound(): void
    {
        $Compounds = new Compounds(new HttpGetter(new Client()), new Users(1, 1), new NullFingerprinter(), false);
        $compoundId = $Compounds->create(name: 'GHS link test', inchiKey: substr(strtoupper(bin2hex(random_bytes(14))), 0, 27));
        // an unknown code is ignored, a withdrawn one is kept from an import, a duplicate is ignored
        $this->GhsStatements->linkToCompound($compoundId, array('P281', 'H300', 'H999', 'H300'));
        $this->GhsStatements->linkToCompound($compoundId, array());
        $statements = $this->GhsStatements->readForCompounds(array($compoundId, $compoundId))[$compoundId];
        $this->assertSame(array('H300', 'P281'), array_column($statements, 'code'));
        $this->assertSame(1, $statements[1]['is_deprecated']);
    }

    public function testReadForNoCompounds(): void
    {
        $this->assertSame(array(), $this->GhsStatements->readForCompounds(array()));
    }
}
