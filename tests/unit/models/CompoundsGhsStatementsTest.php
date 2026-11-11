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

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_filter;
use function array_values;
use function bin2hex;
use function random_bytes;
use function sprintf;
use function strtoupper;
use function substr;

class CompoundsGhsStatementsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Compounds $Compounds;

    private CompoundsGhsStatements $Statements;

    private HttpGetter $httpGetter;

    protected function setUp(): void
    {
        $this->httpGetter = new HttpGetter(new Client());
        // this user has can_manage_compounds
        $this->Compounds = new Compounds($this->httpGetter, new Users(1, 1), new NullFingerprinter(), false);
        $this->Compounds->setId($this->Compounds->create(name: 'GHS statements test', inchiKey: substr(strtoupper(bin2hex(random_bytes(14))), 0, 27)));
        $this->Statements = new CompoundsGhsStatements($this->Compounds);
    }

    public function testGetApiPath(): void
    {
        $this->assertSame(sprintf('api/v2/compounds/%d/ghs_statements/', $this->Compounds->id), $this->Statements->getApiPath());
    }

    public function testAddReadAndRemove(): void
    {
        $this->assertSame(array(), $this->Statements->readAll());
        $id = $this->Statements->postAction(Action::Create, array('code' => 'P210'));
        $this->Statements->postAction(Action::Create, array('code' => ' H225 '));
        // adding it again changes nothing
        $this->assertSame($id, $this->Statements->postAction(Action::Create, array('code' => 'P210')));
        $this->assertSame(array('H225', 'P210'), array_column($this->Statements->readAll(), 'code'));
        $this->assertSame('P210', new CompoundsGhsStatements($this->Compounds, $id)->readOne()['code']);

        // the compound has them too
        $compound = $this->Compounds->readOne();
        $this->assertSame(array('H225', 'P210'), array_column($compound['ghs_statements'], 'code'));
        // and the compounds table can show and search them
        $found = $this->Compounds->readAll($this->Compounds->getQueryParams(new InputBag(array('q' => 'H225', 'limit' => 9999))));
        $row = array_values(array_filter($found, fn(array $c): bool => $c['id'] === $this->Compounds->id));
        $this->assertCount(1, $row);
        $this->assertSame('H225, P210', $row[0]['ghs_codes']);

        $this->assertTrue(new CompoundsGhsStatements($this->Compounds, $id)->destroy());
        $this->assertSame(array('H225'), array_column($this->Statements->readAll(), 'code'));
    }

    public function testReadOneNotLinked(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        new CompoundsGhsStatements($this->Compounds, 1)->readOne();
    }

    public function testAddUnknownCode(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Statements->postAction(Action::Create, array('code' => 'H999'));
    }

    public function testAddWithdrawnCode(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Statements->postAction(Action::Create, array('code' => 'P281'));
    }

    public function testInvalidAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Statements->postAction(Action::Update, array('code' => 'H225'));
    }

    public function testDestroyWithoutId(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Statements->destroy();
    }

    public function testWriteAccess(): void
    {
        $this->Statements->postAction(Action::Create, array('code' => 'H225'));
        // readable by the other teams: a new compound is only visible to its team
        $this->Compounds->patch(Action::Update, array('canread_base' => BasePermissions::Organization->value));
        // a user without can_manage_compounds can read but not modify when edition is restricted
        $Compounds = new Compounds($this->httpGetter, $this->getRandomUserInTeam(2), new NullFingerprinter(), true, $this->Compounds->id);
        $Statements = new CompoundsGhsStatements($Compounds);
        $this->assertSame(array('H225'), array_column($Statements->readAll(), 'code'));
        $this->expectException(ForbiddenException::class);
        $Statements->postAction(Action::Create, array('code' => 'P210'));
    }
}
