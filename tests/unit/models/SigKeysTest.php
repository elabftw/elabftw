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

class SigKeysTest extends \PHPUnit\Framework\TestCase
{
    public const PASSPHRASE = 'correct horse battery staple';

    private SigKeys $SigKeys;

    protected function setUp(): void
    {
        $this->SigKeys = new SigKeys(new Users(1, 1));
    }

    public function testCreateAndDestroy(): void
    {
        $id = $this->SigKeys->postAction(
            Action::Create,
            array('passphrase' => self::PASSPHRASE)
        );
        $this->assertIsInt($id);
        $this->SigKeys->setId($id);
        $this->assertIsArray($this->SigKeys->readOne());
        $this->assertIsArray($this->SigKeys->readAll());
        $this->assertTrue($this->SigKeys->touch());
    }

    public function testPatch(): void
    {
        $this->assertIsArray($this->SigKeys->patch(Action::Update, array('passphrase' => self::PASSPHRASE)));
    }

    public function testGetApiPath(): void
    {
        $this->assertIsString($this->SigKeys->getApiPath());
    }
}
