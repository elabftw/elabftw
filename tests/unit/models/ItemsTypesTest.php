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
        $extra = array(
            'color' => '#faaccc',
            'body' => 'body',
            'canread' => BasePermissions::Team->toJson(),
            'canwrite' => BasePermissions::Team->toJson(),
        );
        $this->ItemsTypes->setId($this->ItemsTypes->create('new'));
        $this->ItemsTypes->patch(Action::Update, $extra);
        $this->assertTrue($this->ItemsTypes->destroy());
    }

    public function testDuplicate(): void
    {
        $this->ItemsTypes->setId($this->ItemsTypes->create('new'));
        $this->expectException(ImproperActionException::class);
        $this->ItemsTypes->duplicate();
    }

    public function testGetPage(): void
    {
        $this->assertIsString($this->ItemsTypes->getPage());
    }
}
