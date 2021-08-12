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
        $this->ItemsTypes= new ItemsTypes(new Users(1, 1));
    }

    public function testCreateUpdateDestroy(): void
    {
        $extra = array(
            'color' => '#faaccc',
            'body' => 'body',
            'canread' => 'team',
            'canwrite' => 'team',
            'bookable' => '0',
        );
        $this->ItemsTypes->create(
            new ItemTypeParams('new', 'all', $extra)
        );
        $itemsTypes = $this->ItemsTypes->readAll();
        $last = array_pop($itemsTypes);
        $this->ItemsTypes->setId((int) $last['category_id']);
        $extra = array(
            'color' => '#fffccc',
            'body' => 'newbody',
            'canread' => 'team',
            'canwrite' => 'team',
            'bookable' => '1',
        );
        $this->ItemsTypes->updateAll(
            new ItemTypeParams('new', 'all', $extra)
        );
        $this->assertEquals('newbody', $this->ItemsTypes->read(new ContentParams())['template']);
        $this->ItemsTypes->setId((int) $last['category_id']);
        $this->ItemsTypes->destroy();
    }
}
