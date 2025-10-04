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
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

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
        // test with a user without can_manage_compounds
        $user = $this->getRandomUserInTeam(2);
        $Compounds = new Compounds($this->httpGetter, $user, new NullFingerprinter(), false);
        // okay because we did not require rights yet
        $Compounds->postAction(Action::Create, array());
        // now lock down edition
        $Compounds = new Compounds($this->httpGetter, $user, new NullFingerprinter(), true);
        $this->expectException(IllegalActionException::class);
        $Compounds->postAction(Action::Create, array());
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

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/compounds/', $this->Compounds->getApiPath());
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
}
