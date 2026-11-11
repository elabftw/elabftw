<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

use function array_column;
use function sprintf;

class CompoundsLinksTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testAll(): void
    {
        $user = $this->getRandomUserInTeam(2);
        $Entity = $this->getFreshExperimentWithGivenUser($user);
        $Links = new Compounds2ExperimentsLinks($Entity);
        $mock = new MockHandler(array(
            new Response(200, array(), 'nothing'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $Compounds = new Compounds($httpGetter, $user, new NullFingerprinter(), false);
        $compoundId = $Compounds->create(name: 'test compound');
        $Links->setId($compoundId);
        $expected = sprintf('api/v2/experiments/%d/compounds2experiments/', $Entity->id);
        $this->assertEquals($expected, $Links->getApiPath());
        $this->assertIsInt($Links->postAction(Action::Create, array()));
        $this->assertTrue($Links->destroy());
        // create with invalid action
        $this->expectException(ImproperActionException::class);
        $Links->postAction(Action::Disable2fa, array());
    }

    public function testCompoundTheUserCannotRead(): void
    {
        $owner = $this->getUserInTeam(2);
        $httpGetter = new HttpGetter(new Client(array('handler' => HandlerStack::create(new MockHandler(array(new Response(200, array(), 'nothing')))))));
        $compoundId = new Compounds($httpGetter, $owner, new NullFingerprinter(), false)
            ->create(name: 'Private compound', canreadBase: BasePermissions::UserOnly, canwriteBase: BasePermissions::UserOnly);
        $Entity = $this->getFreshExperiment();
        $this->assertNotSame($owner->userid, $Entity->Users->userid);

        // it cannot be linked
        try {
            new Compounds2ExperimentsLinks($Entity, $compoundId)->postAction(Action::Create, array());
            $this->fail('Linked a compound the user cannot read');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }

        // and like the links to entries, it is not listed if it is linked anyway (by someone who could read it)
        $req = Db::getConnection()->prepare('INSERT INTO compounds2experiments (entity_id, compound_id) VALUES (:entity, :compound)');
        $req->execute(array(':entity' => $Entity->id, ':compound' => $compoundId));
        $this->assertNotContains($compoundId, array_column(new Compounds2ExperimentsLinks($Entity)->readAll(), 'id'));
        $this->assertContains($compoundId, array_column(new Compounds2ExperimentsLinks(new Experiments($owner, $Entity->id, bypassReadPermission: true))->readAll(), 'id'));
    }
}
