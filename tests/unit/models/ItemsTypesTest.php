<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ImproperActionException;

class ItemsTypesTest extends \PHPUnit\Framework\TestCase
{
    private ItemsTypes $ItemsTypes;

    protected function setUp(): void
    {
        $this->ItemsTypes = new ItemsTypes(new Users(1, 1));
    }

    public function testCreateUpdateDestroy(): void
    {
        // create
        $this->ItemsTypes->setId($this->ItemsTypes->create(body: 'body1', color: '29aeb9'));
        $this->assertEquals('29aeb9', $this->ItemsTypes->entityData['color']);
        $this->assertEquals('body1', $this->ItemsTypes->entityData['body']);
        // update
        $params = array(
            'color' => '#faaccc',
            'body' => 'body2',
            'canread' => BasePermissions::Team->toJson(),
            'canwrite' => BasePermissions::Team->toJson(),
        );
        $this->ItemsTypes->patch(Action::Update, $params);
        $this->assertEquals('faaccc', $this->ItemsTypes->entityData['color']);
        // destroy
        $this->assertTrue($this->ItemsTypes->destroy());
    }

    public function testDuplicate(): void
    {
        $this->ItemsTypes->setId($this->ItemsTypes->create());
        $this->expectException(ImproperActionException::class);
        $this->ItemsTypes->duplicate();
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/items_types/', $this->ItemsTypes->getApiPath());
    }
}
