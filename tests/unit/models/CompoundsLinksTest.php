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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

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
}
