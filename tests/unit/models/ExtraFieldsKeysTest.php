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
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users\Users;

use function array_column;
use function bin2hex;
use function random_bytes;
use function sprintf;

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
        // only one entity type
        $this->assertIsArray((new ExtraFieldsKeys($this->Users, '', -1, EntityType::Experiments))->readAll());
        $this->assertIsArray((new ExtraFieldsKeys($this->Users, '', -1, EntityType::Items))->readAll());
        // other entity types fall back to experiments and items
        $this->assertIsArray((new ExtraFieldsKeys($this->Users, '', -1, EntityType::Templates))->readAll());
    }

    public function testReadKeysOfOneEntityType(): void
    {
        $key = 'onlyInItems' . bin2hex(random_bytes(6));
        $Items = new Items($this->Users);
        $Items->setId($Items->create());
        $Items->patch(Action::Update, array('metadata' => sprintf('{"extra_fields": {"%s": {"type": "text", "value": "a"}}}', $key)));

        $inItems = array_column((new ExtraFieldsKeys($this->Users, $key, -1, EntityType::Items))->readAll(), 'extra_fields_key');
        $this->assertContains($key, $inItems);
        $inExperiments = array_column((new ExtraFieldsKeys($this->Users, $key, -1, EntityType::Experiments))->readAll(), 'extra_fields_key');
        $this->assertNotContains($key, $inExperiments);
    }

    public function testRest(): void
    {
        $ExtraFieldsKeys = new ExtraFieldsKeys($this->Users, '');
        $this->assertIsArray($ExtraFieldsKeys->readOne());
        $this->assertEquals('api/v2/extra_fields_keys/', $ExtraFieldsKeys->getApiPath());
    }
}
