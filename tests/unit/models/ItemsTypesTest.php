<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateItemType;
use Elabftw\Elabftw\ParamsProcessor;

class ItemsTypesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->ItemsTypes= new ItemsTypes(new Users(1, 1));
    }

    public function testCreateUpdateDestroy()
    {
        $this->ItemsTypes->create(
            new CreateItemType('new', '#fffccc', '<p>body</p>', 'team', 'team', 0)
        );
        $itemsTypes = $this->ItemsTypes->readAll();
        $last = array_pop($itemsTypes);
        $this->ItemsTypes->updateAll(
            new ParamsProcessor(
                array(
                    'name' => 'newname',
                    'id' => (int) $last['category_id'],
                    'color' => '#fffccc',
                    'bookable' => 1,
                    'template' => 'newbody',
                )
            )
        );
        $this->ItemsTypes->setId((int) $last['category_id']);
        $this->assertEquals('newbody', $this->ItemsTypes->read()['template']);
        $this->ItemsTypes->destroy((int) $last['category_id']);
    }
}
