<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;

/**
 * For database.php
 */
class DatabaseController extends AbstractEntityController
{
    public function __construct(App $app, Items $entity)
    {
        parent::__construct($app, $entity);

        $Category = new ItemsTypes($this->App->Users);
        $this->categoryArr = $Category->readAll();
    }

    /**
     * Get the results from main sql query with items to display
     */
    protected function getItemsArr(): array
    {
        $DisplayParams = new DisplayParams();
        $DisplayParams->adjust($this->App);
        return $this->Entity->readShow($DisplayParams);
    }
}
