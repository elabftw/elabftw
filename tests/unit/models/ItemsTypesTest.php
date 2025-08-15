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
use Elabftw\Models\Users\Users;

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
        $this->ItemsTypes->setId($this->ItemsTypes->create(body: 'body1', title: 'blah'));
        $this->assertEquals('blah', $this->ItemsTypes->entityData['title']);
        $this->assertEquals('body1', $this->ItemsTypes->entityData['body']);
        // update
        $params = array(
            'title' => 'oompa',
            'body' => 'body2',
            'canread' => BasePermissions::Team->toJson(),
            'canwrite' => BasePermissions::Team->toJson(),
        );
        $this->ItemsTypes->patch(Action::Update, $params);
        $this->assertEquals('oompa', $this->ItemsTypes->entityData['title']);
        // destroy
        $this->assertTrue($this->ItemsTypes->destroy());
    }

    public function testDuplicate(): void
    {
        $title = 'Serge Gainsbourg';
        $body = 'Quand Gainsbarre se bourre, Gainsbourg se barre.';
        $this->ItemsTypes->setId($this->ItemsTypes->create(title: $title, body: $body));
        $newId = $this->ItemsTypes->duplicate();
        $this->ItemsTypes->setId($newId);
        $this->assertEquals($title . ' I', $this->ItemsTypes->entityData['title']);
        $this->assertEquals($body, $this->ItemsTypes->entityData['body']);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/items_types/', $this->ItemsTypes->getApiPath());
    }
}
