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
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;

class CompoundsTest extends \PHPUnit\Framework\TestCase
{
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
        $this->Compounds = new Compounds($this->httpGetter, new Users(1, 1));
    }

    public function testCreateSearchAndDestroy(): void
    {
        $compoundId = $this->Compounds->create(
            casNumber: '58-08-2',
            pubchemCid: 2519,
            smiles: $this->smilesCaf,
            withFingerprint: false,
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
            new Response(200, array(), $this->fpResponse),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $Compounds = new Compounds($httpGetter, new Users(1, 1));
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
        $this->assertEquals('437-38-7', $compound['cas_number']);

        // now try to add the same compound again
        $this->expectException(ImproperActionException::class);
        $Compounds->create(casNumber: self::CAFFEINE_CAS);
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
        $Compounds = new Compounds($httpGetter, new Users(1, 1));

        $queryParams = array('search_fp_smi' => $this->smiles);
        $req = new Request($queryParams);
        $q = $Compounds->getQueryParams($req->query);
        $this->assertIsArray($Compounds->readAll($q));
    }
}
