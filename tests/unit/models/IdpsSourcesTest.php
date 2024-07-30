<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

class IdpsSourcesTest extends \PHPUnit\Framework\TestCase
{
    private IdpsSources $IdpsSources;

    protected function setUp(): void
    {
        $this->IdpsSources = new IdpsSources(new Users(1, 1));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/idps_sources/', $this->IdpsSources->getApiPath());
    }

    public function testNotSysadmin(): void
    {
        $this->expectException(IllegalActionException::class);
        new IdpsSources(new Users(2, 1));
    }

    public function testPatchNoId(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->IdpsSources->patch(Action::Add, array());
    }

    public function testPatchIncorrectAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->IdpsSources->setId(1);
        $this->IdpsSources->patch(Action::Add, array());
    }

    public function testCreate(): void
    {
        $params = array(
            'url' => 'https://source.example.fr/idps.xml/',
        );
        $id = $this->IdpsSources->postAction(Action::Create, $params);
        $this->assertIsInt($id);
        $this->assertIsArray($this->IdpsSources->readAll());
        $this->IdpsSources->setId($id);
        $this->assertIsArray($this->IdpsSources->readOne());
        $this->assertTrue($this->IdpsSources->destroy());
    }
}
