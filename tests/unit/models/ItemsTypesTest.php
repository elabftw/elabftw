<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\ItemTypeParams;

class ItemsTypesTest extends \PHPUnit\Framework\TestCase
{
    private ItemsTypes $ItemsTypes;

    protected function setUp(): void
    {
        $this->ItemsTypes= new ItemsTypes(1);
    }

    public function testCreateUpdateDestroy(): void
    {
        $this->ItemsTypes->create(
            new ItemTypeParams('new', '#fffccc', '<p>body</p>', 'team', 'team', 0)
        );
        $itemsTypes = $this->ItemsTypes->readAll();
        $last = array_pop($itemsTypes);
        $this->ItemsTypes->setId((int) $last['category_id']);
        $this->ItemsTypes->updateAll(
            new ItemTypeParams('new', '#fffccc', 'newbody', 'team', 'team', 1)
        );
        $this->assertEquals('newbody', $this->ItemsTypes->read(new ContentParams())['template']);
        $this->ItemsTypes->setId((int) $last['category_id']);
        $this->ItemsTypes->destroy();
    }
}
