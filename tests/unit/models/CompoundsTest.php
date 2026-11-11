<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\SmartsMatcherInterface;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

use function array_column;
use function array_filter;
use function array_keys;
use function array_search;
use function bin2hex;
use function json_decode;
use function random_bytes;
use function sprintf;
use function str_repeat;
use function strtoupper;
use function substr;

class CompoundsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private const string FENTANYL_CAS = '437-38-7';

    private const string CAFFEINE_CAS = '58-08-2';

    private Compounds $Compounds;

    private HttpGetter $httpGetter;

    // the smiles of cid 3345
    private string $smiles = 'CCC(=O)N(C1CCN(CC1)CCC2=CC=CC=C2)C3=CC=CC=C3';

    // smiles for cid 2519
    private string $smilesCaf = 'CN1C=NC2=C1C(=O)N(C(=O)N2C)C';

    // the json response of fingerprinter for the smiles of cid 3345
    private string $fpResponse = '{"data": [128, 67108864, 0, 524288, 2148007936, 4194304, 0, 2, 16, 35840, 512, 0, 1, 0, 0, 4194304, 0, 1, 0, 67272704, 1073745920, 0, 1048576, 64, 1024, 64, 0, 0, 32, 0, 16777216, 0]}';

    protected function setUp(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), 'nothing'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $this->httpGetter = new HttpGetter($client);
        // this user has can_manage_compounds
        $user = new Users(1, 1);
        $this->Compounds = new Compounds($this->httpGetter, $user, new NullFingerprinter(), false);
    }

    public function testCreateSearchAndDestroy(): void
    {
        $compoundId = $this->Compounds->create(
            casNumber: self::CAFFEINE_CAS,
            pubchemCid: 2519,
            smiles: $this->smilesCaf,
        );
        $this->Compounds->setId($compoundId);
        $this->assertIsArray($this->Compounds->readAll());
        // now with a query param 'q'
        $queryParams = array('q' => '437');
        $req = new Request($queryParams);
        $q = $this->Compounds->getQueryParams($req->query);
        $this->assertIsArray($this->Compounds->readAll($q));
        $this->assertIsArray($this->Compounds->readOne());
        $name = 'yep';
        $new = $this->Compounds->patch(Action::Update, array('name' => $name));
        $this->assertIsArray($new);
        $this->assertEquals($new['name'], $name);
        $this->assertIsInt($compoundId);
        $this->assertTrue($this->Compounds->destroy());
    }

    public function testCreateWithoutMolecularWeight(): void
    {
        // an imported compound can have no known weight, but the column is not nullable
        $compoundId = $this->Compounds->create(name: 'Weightless', molecularWeight: null);
        $this->Compounds->setId($compoundId);
        $this->assertEquals(0, (float) $this->Compounds->readOne()['molecular_weight']);
        $this->assertTrue($this->Compounds->destroy());
    }

    public function testPostAction(): void
    {
        $fixturesFs = Storage::FIXTURES->getStorage()->getFs();
        $testCompound = $fixturesFs->read('cid-3345.json');
        $mock = new MockHandler(array(
            new Response(200, array(), $testCompound),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $Compounds = new Compounds($httpGetter, new Users(1, 1), new NullFingerprinter(), false);
        // https://pubchem.ncbi.nlm.nih.gov/compound/3345
        $cid = 3345;
        $compoundId = $Compounds->postAction(Action::Duplicate, array('cid' => $cid));
        $Compounds->setId($compoundId);
        $compound = $Compounds->readOne();
        $this->assertEquals($cid, $compound['pubchem_cid']);
        $this->assertEquals('N-phenyl-N-[1-(2-phenylethyl)piperidin-4-yl]propanamide', $compound['iupac_name']);
        $this->assertEquals('InChI=1S/C22H28N2O/c1-2-22(25)24(20-11-7-4-8-12-20)21-14-17-23(18-15-21)16-13-19-9-5-3-6-10-19/h3-12,21H,2,13-18H2,1H3', $compound['inchi']);
        $this->assertEquals('PJMPHNIQZUBGLI-UHFFFAOYSA-N', $compound['inchi_key']);
        $this->assertEquals($this->smiles, $compound['smiles']);
        $this->assertEquals(self::FENTANYL_CAS, $compound['cas_number']);
        // the GHS classification of PubChem
        $this->assertSame('Danger', $compound['ghs_signal_word']);
        $codes = array_column($compound['ghs_statements'], 'code');
        $this->assertContains('H300', $codes);
        $this->assertContains('P301+P316', $codes);
        $this->assertSame('IF SWALLOWED: Get emergency medical help immediately.', $compound['ghs_statements'][array_search('P301+P316', $codes, true)]['statement']);
        // test with a user without can_manage_compounds
        $user = $this->getRandomUserInTeam(2);
        $Compounds = new Compounds($this->httpGetter, $user, new NullFingerprinter(), false);
        // okay because we did not require rights yet
        $Compounds->postAction(Action::Create, array());
        // now lock down edition
        $Compounds = new Compounds($this->httpGetter, $user, new NullFingerprinter(), true);
        $this->expectException(ForbiddenException::class);
        $Compounds->postAction(Action::Create, array());
    }

    public function testCreateWithGhs(): void
    {
        $id = $this->Compounds->postAction(Action::Create, array(
            'name' => 'Flammable compound',
            'inchi_key' => substr(strtoupper(bin2hex(random_bytes(14))), 0, 27),
            'ghs_signal_word' => 'Warning',
            // the unknown code is ignored
            'ghs_statements' => 'P210, H226 H999',
        ));
        $this->Compounds->setId($id);
        $compound = $this->Compounds->readOne();
        $this->assertSame('Warning', $compound['ghs_signal_word']);
        $this->assertSame(array('H226', 'P210'), array_column($compound['ghs_statements'], 'code'));
        // a list of codes works too
        $id = $this->Compounds->postAction(Action::Create, array(
            'name' => 'Another flammable compound',
            'inchi_key' => substr(strtoupper(bin2hex(random_bytes(14))), 0, 27),
            'ghs_statements' => array('H226', 'P210'),
        ));
        $this->Compounds->setId($id);
        $this->assertSame(array('H226', 'P210'), array_column($this->Compounds->readOne()['ghs_statements'], 'code'));
        $this->assertNull($this->Compounds->readOne()['ghs_signal_word']);
        // the signal word can be changed and removed
        $this->assertSame('Danger', $this->Compounds->patch(Action::Update, array('ghs_signal_word' => 'Danger'))['ghs_signal_word']);
        $this->assertNull($this->Compounds->patch(Action::Update, array('ghs_signal_word' => ''))['ghs_signal_word']);
        $this->expectException(ImproperActionException::class);
        $this->Compounds->patch(Action::Update, array('ghs_signal_word' => 'Maybe'));
    }

    public function testPostActionBadParam(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Compounds->postAction(Action::Duplicate, array());
    }

    public function testRestoreCompound(): void
    {
        // create a compound
        $Compound = new Compounds($this->httpGetter, new Users(1, 1), new NullFingerprinter(), false);
        $compoundId = $this->Compounds->create(casNumber: self::CAFFEINE_CAS, pubchemCid: 2519, smiles: $this->smilesCaf);
        $Compound->setId($compoundId);
        $compound = $Compound->readOne();
        $this->assertEquals(State::Normal->value, $compound['state']);

        // delete it
        $Compound->patch(Action::Update, array('state' => State::Deleted->value));

        // restore it
        $restoredCompoundId = $Compound->create(casNumber: self::CAFFEINE_CAS, pubchemCid: 2519, smiles: $this->smilesCaf);
        $Compound->setId($restoredCompoundId);
        $restoredCompound = $Compound->readOne();
        $this->assertEquals(State::Normal->value, $restoredCompound['state']);
    }

    public function testDestroyLinkedCompound(): void
    {
        $compoundId = $this->Compounds->create(
            casNumber: self::CAFFEINE_CAS,
            pubchemCid: 2519,
            smiles: $this->smilesCaf,
        );
        $this->Compounds->setId($compoundId);
        $experiment = $this->getFreshExperiment();
        $linker = new Compounds2ExperimentsLinks($experiment, $compoundId);
        $linker->postAction(Action::Create, array());
        $this->expectException(ImproperActionException::class);
        $this->Compounds->destroy();
    }

    public function testPermissions(): void
    {
        // only the owner can read and write it
        $owner = $this->getUserInTeam(2);
        // a logged in user, as Organization access is only for them, see Permissions::getCan()
        $other = $this->getUserInTeam(1);
        $this->assertNotSame($other->userid, $owner->userid);
        $OwnerCompounds = new Compounds($this->httpGetter, $owner, new NullFingerprinter(), false);
        $inchiKey = substr(strtoupper(bin2hex(random_bytes(14))), 0, 27);
        $id = $OwnerCompounds->create(name: 'Private compound', inchiKey: $inchiKey, canreadBase: BasePermissions::UserOnly, canwriteBase: BasePermissions::UserOnly);
        $OwnerCompounds->setId($id);
        $this->assertSame(1, $OwnerCompounds->readOne()['has_write_access']);

        $OtherCompounds = new Compounds($this->httpGetter, $other, new NullFingerprinter(), false, $id);
        $this->assertNotContains($id, array_column($OtherCompounds->readAll($OtherCompounds->getQueryParams(new InputBag(array('limit' => 9999)))), 'id'));
        $this->assertForbidden(fn() => $OtherCompounds->readOne());
        $this->assertForbidden(fn() => $OtherCompounds->patch(Action::Update, array('name' => 'Renamed')));
        $this->assertForbidden(fn() => $OtherCompounds->destroy());
        // the identifiers are unique: the other user cannot add it again nor use it
        try {
            $OtherCompounds->create(name: 'Same compound', inchiKey: $inchiKey);
            $this->fail('Created a compound with the identifier of a compound it cannot read');
        } catch (ImproperActionException) {
            $this->addToAssertionCount(1);
        }

        // shared for reading only
        $OwnerCompounds->patch(Action::Update, array('canread' => sprintf('{"teams": [], "teamgroups": [], "users": [%d]}', $other->userid)));
        $compound = $OtherCompounds->readOne();
        $this->assertSame(0, $compound['has_write_access']);
        $this->assertSame($other->userid, $compound['canread_human']['users'][0]['userid']);
        $this->assertContains($id, array_column($OtherCompounds->readAll($OtherCompounds->getQueryParams(new InputBag(array('limit' => 9999)))), 'id'));
        $this->assertForbidden(fn() => $OtherCompounds->patch(Action::Update, array('name' => 'Renamed')));
        // adding it again gives the existing one, unchanged
        $this->assertSame($id, $OtherCompounds->create(name: 'Renamed', inchiKey: $inchiKey));
        $this->assertSame('Private compound', $OwnerCompounds->readOne()['name']);

        // and for writing
        $OwnerCompounds->patch(Action::Update, array('canwrite_base' => BasePermissions::Organization->value));
        $this->assertSame('Renamed', $OtherCompounds->patch(Action::Update, array('name' => 'Renamed'))['name']);
    }

    public function testCreateWithPermissions(): void
    {
        $id = $this->Compounds->postAction(Action::Create, array(
            'name' => 'Shared with a team',
            'canread_base' => BasePermissions::User->value,
            'canread' => '{"teams": [2], "teamgroups": [], "users": []}',
        ));
        $this->Compounds->setId($id);
        $compound = $this->Compounds->readOne();
        $this->assertSame(BasePermissions::User->value, $compound['canread_base']);
        $this->assertSame(Compounds::DEFAULT_CANWRITE_BASE->value, $compound['canwrite_base']);
        $this->assertSame(array(2), json_decode($compound['canread'], true)['teams']);

        $this->expectException(ImproperActionException::class);
        $this->Compounds->postAction(Action::Create, array('name' => 'Invalid', 'canread' => '{"teams": "all"}'));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/compounds/', $this->Compounds->getApiPath());
    }

    public function testAnonymousUserCannotReadAll(): void
    {
        $Compounds = new Compounds($this->httpGetter, new AnonymousUser(1), new NullFingerprinter(), false);

        $this->expectException(ForbiddenException::class);

        $Compounds->readAll();
    }

    public function testAnonymousUserCannotReadOne(): void
    {
        $Compounds = new Compounds($this->httpGetter, new AnonymousUser(1), new NullFingerprinter(), false, 1);

        $this->expectException(ForbiddenException::class);

        $Compounds->readOne();
    }

    public function testReadAll(): void
    {
        $queryParams = array('search_pubchem_cid' => '3345');
        $req = new Request($queryParams);
        $q = $this->Compounds->getQueryParams($req->query);
        $this->assertIsArray($this->Compounds->readAll($q));

        $mock = new MockHandler(array(
            new Response(200, array(), $this->fpResponse),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $Compounds = new Compounds($httpGetter, new Users(1, 1), new NullFingerprinter(), false);

        $queryParams = array('search_fp_smi' => $this->smiles);
        $req = new Request($queryParams);
        $q = $Compounds->getQueryParams($req->query);
        $this->assertIsArray($Compounds->readAll($q));
    }

    public function testSearchSmarts(): void
    {
        $smiles = 'N' . str_repeat('C', 40) . bin2hex(random_bytes(2));
        $compoundId = $this->Compounds->create(name: 'smarts search', smiles: $smiles);
        // only the structure of this compound matches, whatever the pattern
        $matcher = new class ($smiles) implements SmartsMatcherInterface {
            public string $pattern = '';

            public function __construct(private string $match) {}

            public function matches(string $smarts, array $smiles, bool $exact = false): array
            {
                $this->pattern = $smarts;
                return array_keys(array_filter($smiles, fn(string $structure): bool => $structure === $this->match));
            }
        };
        $Compounds = new Compounds($this->httpGetter, new Users(1, 1), new NullFingerprinter(), false, smartsMatcher: $matcher);
        $results = $Compounds->readAll($Compounds->getQueryParams(new InputBag(array('search_smarts' => ' [NX3]C '))));
        $this->assertSame('[NX3]C', $matcher->pattern);
        $this->assertEquals(array($compoundId), array_column($results, 'id'));

        $this->expectException(ImproperActionException::class);
        $Compounds->readAll($Compounds->getQueryParams(new InputBag(array('search_smarts' => str_repeat('C', 2049)))));
    }

    private function assertForbidden(callable $action): void
    {
        try {
            $action();
            $this->fail('Allowed a forbidden action');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }
    }
}
