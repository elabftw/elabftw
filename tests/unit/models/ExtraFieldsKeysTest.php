<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;

class ExtraFieldsKeysTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
    }

    public function testReadKeys(): void
    {
        // default user limit
        $this->assertIsArray((new ExtraFieldsKeys($this->Users, ''))->readAll());
        // no limit
        $this->assertIsArray((new ExtraFieldsKeys($this->Users, '', -1))->readAll());
        // custom limit
        $this->assertIsArray((new ExtraFieldsKeys($this->Users, '', 100))->readAll());
    }

    public function testRest(): void
    {
        $ExtraFieldsKeys = new ExtraFieldsKeys($this->Users, '');
        $this->assertIsArray($ExtraFieldsKeys->readOne());
        $this->assertIsArray($ExtraFieldsKeys->patch(Action::Update, array()));
        $this->assertEquals(0, $ExtraFieldsKeys->postAction(Action::Create, array()));
        $this->assertEquals('extra_fields_keys', $ExtraFieldsKeys->getPage());
        $this->assertFalse($ExtraFieldsKeys->destroy());
    }
}
