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
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Enums\FilterableColumn;
use Elabftw\Models\Experiments;
use Elabftw\Models\Status;
use Elabftw\Models\Teams;

/**
 * For experiments.php
 */
class ExperimentsController extends AbstractEntityController
{
    /**
     * Constructor
     */
    public function __construct(App $app, Experiments $entity)
    {
        parent::__construct($app, $entity);

        $Category = new Status(new Teams($this->App->Users, $this->App->Users->team));
        $this->categoryArr = $Category->readAll();
    }

    /**
     * Get the results from main sql query with items to display
     */
    protected function getItemsArr(): array
    {
        $DisplayParams = new DisplayParams($this->App->Users, $this->App->Request, $this->Entity->entityType);
        // filter by user if we don't want to show the rest of the team
        // looking for an owner will bypass the user preference
        // same with an extended search: we show all
        if (!$this->Entity->Users->userData['show_team'] && !$this->App->Request->query->has('owner') && !$this->App->Request->query->has('extended')) {
            // Note: the cast to int is necessary here (not sure why)
            $DisplayParams->appendFilterSql(FilterableColumn::Owner, (int) $this->App->Users->userData['userid']);
        }

        return $this->Entity->readShow($DisplayParams);
    }
}
