<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\Teams;

/**
 * For search.php
 */
class SearchController extends AbstractEntityController
{
    public function __construct(App $app, AbstractEntity $entity)
    {
        parent::__construct($app, $entity);

        // on search page, the categories can be status or itemstypes depending on where one searches
        if ($this->App->Request->query->get('type') === 'experiments') {
            $Category = new ExperimentsStatus(new Teams($this->App->Users, $this->App->Users->team));
        } else {
            $Category = new ItemsTypes($this->App->Users);
        }
        $this->categoryArr = $Category->readAll();
    }
}
